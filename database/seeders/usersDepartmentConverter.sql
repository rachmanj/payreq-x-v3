UPDATE approval_stages SET department_id = 100 WHERE department_id = 1 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 200 WHERE department_id = 2 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 300 WHERE department_id = 3 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 400 WHERE department_id = 4 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 500 WHERE department_id = 5 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 600 WHERE department_id = 6 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 700 WHERE department_id = 7 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 800 WHERE department_id = 8 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 900 WHERE department_id = 9 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 1000 WHERE department_id = 10 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 1200 WHERE department_id = 12 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 1300 WHERE department_id = 13 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 1400 WHERE department_id = 14 AND created_at > '2024-05-01';
UPDATE approval_stages SET department_id = 1600 WHERE department_id = 16 AND created_at > '2024-05-01';



UPDATE approval_stages SET department_id = 5 WHERE department_id = 100;
UPDATE approval_stages SET department_id = 14 WHERE department_id = 200;
UPDATE approval_stages SET department_id = 19 WHERE department_id = 300;
UPDATE approval_stages SET department_id = 12 WHERE department_id = 400;
UPDATE approval_stages SET department_id = 10 WHERE department_id = 500;
UPDATE approval_stages SET department_id = 15 WHERE department_id = 600;
UPDATE approval_stages SET department_id = 13 WHERE department_id = 700;
UPDATE approval_stages SET department_id = 20 WHERE department_id = 800;
UPDATE approval_stages SET department_id = 8 WHERE department_id = 900;
UPDATE approval_stages SET department_id = 11 WHERE department_id = 1000;
UPDATE approval_stages SET department_id = 4 WHERE department_id = 1200;
UPDATE approval_stages SET department_id = 18 WHERE department_id = 1300;
UPDATE approval_stages SET department_id = 17 WHERE department_id = 1400;
UPDATE approval_stages SET department_id = 6 WHERE department_id = 1600;


UPDATE realizations SET department_id = 14 WHERE id = 1144;
UPDATE verification_journal_details SET account_code = '11100018' WHERE id = 10;
UPDATE verification_journal_details SET account_code = '11100018' WHERE id = 12;

-- Payreq belum realisasi DONE!!!
UPDATE payreqs SET department_id = 20 WHERE nomor = "24010000885";
UPDATE payreqs SET department_id = 20 WHERE nomor = "24010000888";
UPDATE payreqs SET department_id = 19 WHERE nomor = "23P0000241";
UPDATE payreqs SET department_id = 19 WHERE nomor = "24010000581";
UPDATE payreqs SET department_id = 19 WHERE nomor = "24010000683";
UPDATE payreqs SET department_id = 19 WHERE nomor = "24010000715";
UPDATE payreqs SET department_id = 13 WHERE nomor = "24010000850";
UPDATE payreqs SET department_id = 5 WHERE nomor = "24010000539";
UPDATE payreqs SET department_id = 5 WHERE nomor = "24010000770";
UPDATE payreqs SET department_id = 6 WHERE nomor = "24010000856";
UPDATE payreqs SET department_id = 14 WHERE nomor = "24010000852";
UPDATE payreqs SET department_id = 18 WHERE nomor = "24010000872";
UPDATE payreqs SET department_id = 18 WHERE nomor = "24010000886";
UPDATE payreqs SET department_id = 12 WHERE nomor = "24010000806";
UPDATE payreqs SET department_id = 12 WHERE nomor = "24010000808";
UPDATE payreqs SET department_id = 12 WHERE nomor = "24010000810";
UPDATE payreqs SET department_id = 12 WHERE nomor = "24010000811";

--realization belum verifikasi DONE!!!
UPDATE realizations SET department_id = 10 WHERE nomor = "24020000859";
UPDATE realizations SET department_id = 10 WHERE nomor = "24020000860";
UPDATE realizations SET department_id = 10 WHERE nomor = "24020000861";
UPDATE realizations SET department_id = 10 WHERE nomor = "24020000862";
UPDATE realizations SET department_id = 10 WHERE nomor = "24020000863";
UPDATE realizations SET department_id = 10 WHERE nomor = "24020000864";
UPDATE realizations SET department_id = 19 WHERE nomor = "24020000738";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000371";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000392";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000398";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000449";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000780";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000841";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000847";
UPDATE realizations SET department_id = 13 WHERE nomor = "24020000849";
UPDATE realizations SET department_id = 5 WHERE nomor = "24020000446";
UPDATE realizations SET department_id = 19 WHERE nomor = "24020000854";
UPDATE realizations SET department_id = 18 WHERE nomor = "24020000865";

-- realization detail DONE!!!
UPDATE realization_details SET department_id = 10 WHERE realization_id = 1153;
UPDATE realization_details SET department_id = 10 WHERE realization_id = 1154;
UPDATE realization_details SET department_id = 10 WHERE realization_id = 1155;
UPDATE realization_details SET department_id = 10 WHERE realization_id = 1156;
UPDATE realization_details SET department_id = 10 WHERE realization_id = 1157;
UPDATE realization_details SET department_id = 10 WHERE realization_id = 1159;
UPDATE realization_details SET department_id = 19 WHERE realization_id = 1027;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 638;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 663;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 639;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 728;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 1073;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 1136;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 1074;
UPDATE realization_details SET department_id = 13 WHERE realization_id = 1146;
UPDATE realization_details SET department_id = 5 WHERE realization_id = 724;
UPDATE realization_details SET department_id = 19 WHERE realization_id = 1142;
UPDATE realization_details SET department_id = 18 WHERE realization_id = 1160;

