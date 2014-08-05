CREATE TABLE IF NOT EXISTS kov_header (
  kov_nr int(11) NOT NULL DEFAULT '0',
  vge_nr int(11) DEFAULT NULL,
  corr_naam varchar(128) DEFAULT NULL,
  ov_datum varchar(45) DEFAULT NULL,
  vge_adres varchar(128) DEFAULT NULL,
  type varchar(45) DEFAULT NULL,
  prijs varchar(45) DEFAULT NULL,
  notaris varchar(75) DEFAULT NULL,
  tax_waarde varchar(45) DEFAULT NULL,
  taxateur varchar(45) DEFAULT NULL,
  tax_datum varchar(45) DEFAULT NULL,
  bouwkundige varchar(45) DEFAULT NULL,
  bouw_datum varchar(45) DEFAULT NULL,
  definitief varchar(45) DEFAULT NULL,
  PRIMARY KEY (kov_nr)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
