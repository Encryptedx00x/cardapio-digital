INSERT IGNORE INTO categories (name, max_selection, sort_order) VALUES
    ('Entradas', 0, 1),
    ('Pratos principais', 0, 2),
    ('Sobremesas', 0, 3),
    ('Bebidas', 0, 4);

INSERT INTO products (category_id, name, description, ingredients, price, image_url, available)
SELECT c.id, sample.name, sample.description, sample.ingredients, sample.price, '', 1
FROM categories c
JOIN (
    SELECT 'Entradas' category, 'Bruschetta' name, 'Pão tostado com tomate e manjericão.' description,
           'Pão, tomate, manjericão, azeite' ingredients, 18.00 price
    UNION ALL SELECT 'Pratos principais', 'Lasanha', 'Lasanha artesanal ao molho de tomate.',
           'Massa, tomate, queijo, molho branco' ingredients, 42.00
    UNION ALL SELECT 'Sobremesas', 'Pudim', 'Pudim de leite condensado.',
           'Leite, ovos, açúcar' ingredients, 14.00
    UNION ALL SELECT 'Bebidas', 'Suco de laranja', 'Suco natural, feito na hora.',
           'Laranja' ingredients, 9.00
) sample ON sample.category = c.name
WHERE NOT EXISTS (
    SELECT 1 FROM products p WHERE p.category_id = c.id AND p.name = sample.name
);
