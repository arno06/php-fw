CREATE TABLE `captcha_challenges` (
   `token_cc` VARCHAR(45) NOT NULL,
   `challenge_cc` VARCHAR(45) NULL,
   `creation_date_cc` DATETIME NULL,
   `validation_date_cc` DATETIME NULL,
   `attempts_cc` INT(1) NULL,
   `setup_cc` JSON NULL,
   PRIMARY KEY (`token_cc`));
