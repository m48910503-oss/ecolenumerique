-- Schema v70 : remove teacher category proposal workflow.

SET @old_foreign_key_checks := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS category_proposals;
SET FOREIGN_KEY_CHECKS = @old_foreign_key_checks;
