-- find payreqs
SELECT * FROM `payreqs` WHERE `user_id` = 23 AND `rab_id` = 186;

-- update record
UPDATE `payreqs` SET `rab_id` = 36 WHERE `user_id` = 23 AND `rab_id` = 186;
UPDATE `payreqs` SET `rab_id` = 37 WHERE `user_id` = 23 AND `rab_id` = 187;
UPDATE `payreqs` SET `rab_id` = 38 WHERE `user_id` = 23 AND `rab_id` = 188;
UPDATE `payreqs` SET `rab_id` = 39 WHERE `user_id` = 23 AND `rab_id` = 190;
UPDATE `payreqs` SET `rab_id` = 40 WHERE `user_id` = 23 AND `rab_id` = 191;
UPDATE `payreqs` SET `rab_id` = 41 WHERE `user_id` = 23 AND `rab_id` = 193;
UPDATE `payreqs` SET `rab_id` = 42 WHERE `user_id` = 23 AND `rab_id` = 194;
UPDATE `payreqs` SET `rab_id` = 43 WHERE `user_id` = 23 AND `rab_id` = 195;
UPDATE `payreqs` SET `rab_id` = 44 WHERE `user_id` = 23 AND `rab_id` = 196;
UPDATE `payreqs` SET `rab_id` = 45 WHERE `user_id` = 23 AND `rab_id` = 197;
UPDATE `payreqs` SET `rab_id` = 46 WHERE `user_id` = 23 AND `rab_id` = 198;
UPDATE `payreqs` SET `rab_id` = 47 WHERE `user_id` = 23 AND `rab_id` = 199;
UPDATE `payreqs` SET `rab_id` = 48 WHERE `user_id` = 23 AND `rab_id` = 200;

-- update anggarans set old_rab_id = null where old_rab_id = 176
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 186;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 187;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 188;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 190;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 191;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 193;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 194;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 195;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 196;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 197;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 198;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 199;
UPDATE `anggarans` SET `old_rab_id` = NULL WHERE `old_rab_id` = 200;
