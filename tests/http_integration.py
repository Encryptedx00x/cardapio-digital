"""Run against an EMPTY, dedicated cardapio_test_* database; never drops data."""
import csv
import html
import http.cookiejar
import io
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
PASSWORD = 'Synthetic-integration-password-2026!'
CHECKS = 0


def check(condition, message):
    global CHECKS
    if not condition:
        raise AssertionError(message)
    CHECKS += 1
    print(f'PASS {CHECKS}: {message}', flush=True)


def php(code, payload=None, extra_env=None):
    result = subprocess.run(
        ['php', '-r', code], cwd=ROOT, text=True, encoding='utf-8',
        input=json.dumps(payload) if payload is not None else '',
        capture_output=True, env={**os.environ, **(extra_env or {})},
    )
    if result.returncode:
        raise RuntimeError(result.stderr)
    return result.stdout


def sql(query, params=()):
    return json.loads(php(
        'require "db.php"; $p=json_decode(stream_get_contents(STDIN),true); '
        '$s=$pdo->prepare($p["sql"]); $s->execute($p["params"]); '
        'echo json_encode($s->fetchAll(),JSON_THROW_ON_ERROR);',
        {'sql': query, 'params': list(params)},
    ))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Client:
    def __init__(self, origin):
        self.origin = origin
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            NoRedirect(), urllib.request.HTTPCookieProcessor(self.jar)
        )

    def request(self, route, data=None):
        data = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
        try:
            result = self.opener.open(self.origin + route, data=data, timeout=10)
        except urllib.error.HTTPError as error:
            result = error
        return result.status, result.headers, result.read().decode('utf-8-sig')

    def token(self, route):
        status, headers, body = self.request(route)
        check(status == 200, f'{route} renders')
        match = re.search(r'name="csrf_token" value="([^"]+)"', body)
        check(match is not None and len(match[1]) == 64, f'{route} supplies CSRF')
        check(any(c.name == 'PHPSESSID' for c in self.jar), f'{route} starts session before output')
        return html.unescape(match[1])


def run():
    name = os.environ.get('DB_NAME', '')
    if not name.startswith('cardapio_test_') or os.environ.get('DB_HOST') not in ('127.0.0.1', 'localhost'):
        raise SystemExit('Use DB_HOST=127.0.0.1 and an EMPTY dedicated DB_NAME=cardapio_test_*.')
    if sql('SHOW TABLES'):
        raise SystemExit('Refusing to modify a nonempty database. Provision a new empty test database.')
    for script in ('schema', 'seed', 'schema', 'seed'):
        php(f'require "db.php"; $pdo->exec(file_get_contents("database/{script}.sql"));')
    check(sql('SELECT COUNT(*) n FROM categories')[0]['n'] == 4, 'schema and seed are repeatable')
    check(sql('SELECT COUNT(*) n FROM products')[0]['n'] == 4, 'seed has four products without duplicates')
    mode = sql('SELECT @@sql_mode value')[0]['value']
    check('STRICT' in mode and 'ONLY_FULL_GROUP_BY' in mode, 'real MariaDB uses strict SQL mode')
    admin = subprocess.run(
        ['php', 'bin/create_admin.php', 'Audit Admin', 'audit@example.test'], cwd=ROOT,
        env={**os.environ, 'ADMIN_PASSWORD': PASSWORD}, capture_output=True, text=True,
    )
    check(admin.returncode == 0, f'admin CLI works with actual schema: {admin.stderr}')
    check(sql("SELECT role,dietary_restrictions FROM users WHERE email='audit@example.test'")[0] == {'role': 'admin', 'dietary_restrictions': ''}, 'admin has explicit role and required fields')

    with socket.socket() as candidate:
        candidate.bind(('127.0.0.1', 0))
        port = candidate.getsockname()[1]
    origin = f'http://127.0.0.1:{port}'
    with tempfile.TemporaryFile(mode='w+', encoding='utf-8') as log:
        server = subprocess.Popen(
            ['php', '-d', 'output_buffering=0', '-d', 'display_errors=0', '-S', f'127.0.0.1:{port}'],
            cwd=ROOT, stdout=log, stderr=log,
            creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0),
        )
        try:
            for _ in range(100):
                try:
                    with socket.create_connection(('127.0.0.1', port), timeout=.2):
                        break
                except OSError:
                    time.sleep(.05)
            guest = Client(origin)
            status, headers, body = guest.request('/public/index.php')
            check(status == 200 and 'Bruschetta' in body, 'public menu reads seeded products from database')
            for route, expected in (('/admin/painel.php', '/admin/login.php'), ('/api/enviar_menu.php', '/public/login.php')):
                status, headers, _ = guest.request(route)
                check(status == 302 and headers['Location'] == expected, 'unauthenticated route redirects correctly on Windows: ' + route)
            for route in ('/tests/security_smoke.php', '/bin/create_admin.php'):
                check(guest.request(route)[0] == 404, 'CLI-only file rejects HTTP: ' + route)

            customer = Client(origin)
            customer_token = customer.token('/public/register.php')
            registration = {'csrf_token': customer_token, 'nome': '=2+3', 'email': 'customer@admin.com', 'senha': PASSWORD, 'telefone': '555-0100', 'restricoes': '<script>dietaryAudit()</script>', 'role': 'admin'}
            check(customer.request('/api/cadastro.php', {**registration, 'csrf_token': ''})[0] == 403, 'registration rejects missing CSRF')
            check(customer.request('/api/cadastro.php', registration)[0] == 302, 'customer registration persists')
            check(customer.request('/api/cadastro.php', registration)[0] == 409, 'duplicate customer is a conflict')
            check(sql("SELECT role FROM users WHERE email='customer@admin.com'")[0]['role'] == 'customer', 'email suffix and submitted role cannot grant admin')
            login_token = customer.token('/public/login.php')
            check(customer.request('/api/login.php', {'csrf_token': login_token, 'email': "' OR 1=1 --", 'senha': PASSWORD})[0] == 401, 'SQL injection cannot bypass customer login')
            check(customer.request('/api/login.php', {'csrf_token': login_token, 'email': 'customer@admin.com', 'senha': PASSWORD})[0] == 302, 'customer login works')
            check(customer.request('/admin/painel.php')[0] == 302, 'customer cannot enter admin panel')
            check(customer.request('/admin/exportar.php')[0] == 302, 'customer cannot export customer data')

            admin_client = Client(origin)
            token = admin_client.token('/admin/login.php')
            old_session = next(c.value for c in admin_client.jar if c.name == 'PHPSESSID')
            check(admin_client.request('/admin/login_proc.php', {'csrf_token': token, 'email': 'customer@admin.com', 'senha': PASSWORD})[0] == 401, 'customer cannot use admin login')
            check(admin_client.request('/admin/login_proc.php', {'csrf_token': token, 'email': 'audit@example.test', 'senha': PASSWORD})[0] == 302, 'admin authentication works')
            new_session = next(c.value for c in admin_client.jar if c.name == 'PHPSESSID')
            check(old_session != new_session, 'login regenerates session ID')
            token = admin_client.token('/admin/painel.php')

            def action(values, expected=302):
                status, _, body = admin_client.request('/admin/painel.php', {'csrf_token': token, **values})
                check(status == expected, f'admin action {values["action"]}: HTTP {status}, expected {expected}')
                return body

            before = sql('SELECT COUNT(*) n FROM categories')[0]['n']
            action({'action': 'create_category', 'name': 'No CSRF', 'csrf_token': ''}, 403)
            check(sql('SELECT COUNT(*) n FROM categories')[0]['n'] == before, 'rejected CSRF causes no writes')
            category_name = "Audit'); DROP TABLE products;--"
            action({'action': 'create_category', 'name': category_name, 'max_selection': 1})
            category = sql('SELECT id FROM categories WHERE name=?', [category_name])[0]['id']
            action({'action': 'create_category', 'name': category_name, 'max_selection': 1}, 409)
            action({'action': 'update_category', 'id': category, 'name': category_name, 'max_selection': 1})
            product = {'action': 'create_product', 'category_id': category, 'name': 'Audit <script>productAudit()</script>', 'description': '<img src=x onerror=descriptionAudit()>', 'ingredients': 'Water', 'price': '12.50', 'image_url': ''}
            action({**product, 'price': '-1'}, 400)
            action({**product, 'image_url': 'javascript:alert(1)'}, 400)
            action(product)
            item_id = sql('SELECT id FROM products WHERE name=?', [product['name']])[0]['id']
            action({**product, 'action': 'update_product', 'id': item_id, 'price': '13.70'})
            check(str(sql('SELECT price FROM products WHERE id=?', [item_id])[0]['price']) == '13.70', 'updated price survives database read')
            status, _, menu = guest.request('/public/index.php')
            check(status == 200 and '&lt;script&gt;productAudit()&lt;/script&gt;' in menu and '<script>productAudit()' not in menu and '&lt;img src=x onerror=descriptionAudit()&gt;' in menu, 'stored product XSS is escaped in public menu after reload')
            action({'action': 'toggle_product', 'id': item_id})
            check('productAudit()' not in guest.request('/public/index.php')[2], 'unavailable product is hidden on public menu')
            customer_token = customer.token('/public/painel.php?tipo=agape_simples')
            order = {'csrf_token': customer_token, 'tipo': 'agape_simples', 'itens[]': [item_id], 'observacoes': '<script>orderAudit()</script>'}
            check(customer.request('/api/enviar_menu.php', order)[0] == 400, 'API rejects unavailable product')
            action({'action': 'toggle_product', 'id': item_id})
            action({**product, 'name': 'Second audit product'})
            second_id = sql('SELECT id FROM products WHERE name=?', ['Second audit product'])[0]['id']
            check(customer.request('/api/enviar_menu.php', {**order, 'itens[]': [item_id, second_id]})[0] == 400, 'API enforces category selection limit')
            check(customer.request('/api/enviar_menu.php', {**order, 'itens[]': [item_id, 'invalid-id']})[0] == 400, 'API rejects mixed invalid selection without partial order')
            check(customer.request('/api/enviar_menu.php', {**order, 'csrf_token': ''})[0] == 403, 'order API rejects missing CSRF')
            check(customer.request('/api/enviar_menu.php', order)[0] == 200, 'order API persists valid selection')
            order_id = sql('SELECT id FROM orders ORDER BY id DESC LIMIT 1')[0]['id']
            dashboard = admin_client.request('/admin/painel.php')[2]
            check('&lt;script&gt;orderAudit()&lt;/script&gt;' in dashboard and '<script>orderAudit()' not in dashboard, 'stored order XSS is escaped in admin')
            action({'action': 'order_status', 'id': order_id, 'status': 'accepted'})
            check(sql('SELECT status FROM orders WHERE id=?', [order_id])[0]['status'] == 'accepted', 'order status persists')
            status, _, export = admin_client.request('/admin/exportar.php')
            check(status == 200, 'CSV export works under ONLY_FULL_GROUP_BY')
            rows = list(csv.DictReader(io.StringIO(export), delimiter=';'))
            row = next(r for r in rows if r['Pedido'] == str(order_id))
            check('productAudit()' in row['Itens'] and row['Observações'] == order['observacoes'], 'CSV columns retain items and observations in correct positions')
            check(row['Nome'] == "'=2+3", 'CSV protects formula-like customer name')
            action({'action': 'delete_category', 'id': category}, 409)
            action({'action': 'delete_product', 'id': item_id})
            snapshot = sql('SELECT product_id,product_name FROM order_items WHERE order_id=?', [order_id])[0]
            check(snapshot['product_id'] is None and 'productAudit()' in snapshot['product_name'], 'product deletion preserves historical order snapshot')
            action({'action': 'delete_order', 'id': order_id})
            check(not sql('SELECT id FROM order_items WHERE order_id=?', [order_id]), 'order deletion cascades only its synthetic items')
            action({'action': 'delete_product', 'id': second_id})
            action({'action': 'delete_category', 'id': category})
            check(not sql('SELECT id FROM categories WHERE id=?', [category]), 'category deletion persists')
            check(sql('SELECT COUNT(*) n FROM products')[0]['n'] == 4, 'SQL injection attempts and audit CRUD leave seed intact')
        finally:
            server.terminate()
            server.wait(timeout=5)
            log.seek(0)
            errors = log.read()
            if 'PHP Fatal error' in errors or 'PHP Warning' in errors:
                print(errors)
                raise AssertionError('HTTP test produced PHP warnings/fatals')
    print(json.dumps({'result': 'PASS', 'checks': CHECKS, 'database': name, 'database_version': sql('SELECT VERSION() version')[0]['version']}))


if __name__ == '__main__':
    run()
