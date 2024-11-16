-- delete records from invoice_creations where created_date is october 2024
DELETE FROM invoice_creations WHERE create_date BETWEEN '2024-09-01' AND '2024-09-30';

-- update wtax23s table
UPDATE wtax23s SET doc_type = 'out' WHERE doc_type = 'invoice';