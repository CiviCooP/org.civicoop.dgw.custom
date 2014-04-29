CREATE TABLE IF NOT EXISTS kov_import (
  kov_nr int(11) DEFAULT NULL,
  vge_nr int(11) DEFAULT NULL,
  pers_nr int(11) DEFAULT NULL,
  corr_naam varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  ov_datum varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  vge_adres varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  type varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  prijs varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  notaris varchar(75) COLLATE utf8_unicode_ci DEFAULT NULL,
  tax_waarde varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  taxateur varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  tax_datum varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  bouwkundige varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  bouw_datum varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  definitief varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY(kov_nr, pers_nr)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
