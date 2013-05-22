#
# Table structure for table 'tx_dam'
#
CREATE TABLE tx_dam (
    tx_dammam_mamuuid tinytext,
    tx_dammam_mime_type tinytext,
    tx_dammam_file_type tinytext,
    tx_dammam_file_name tinytext,
    tx_dammam_color_space tinytext,
    tx_dammam_hpixels tinytext,
    tx_dammam_vpixels tinytext,
    tx_dammam_related_files text,
    tx_dammam_language tinytext,
    tx_dammam_bedienungsanleitung tinytext,
    tx_dammam_assetname_de varchar(255) DEFAULT '',
    tx_dammam_assetname_en varchar(255) DEFAULT '',
    tx_dammam_assetname_fr varchar(255) DEFAULT '',
    tx_dammam_assetname_es varchar(255) DEFAULT '',
    tx_dammam_assetname_it varchar(255) DEFAULT '',
    tx_dammam_assetname_nl varchar(255) DEFAULT '',
    tx_dammam_assetname_ru varchar(255) DEFAULT '',
    tx_dammam_assetname_pl varchar(255) DEFAULT '',
    tx_dammam_assetname_hu varchar(255) DEFAULT '',
    tx_dammam_assetname_cs varchar(255) DEFAULT '',
    tx_dammam_assetname_zhen varchar(255) DEFAULT '',
    tx_dammam_assetname_zh varchar(255) DEFAULT ''
);

#
# Table structure for table 'tx_dammam_unresolved_relations'
#
CREATE TABLE tx_dammam_unresolved_relations (
    crdate int(11) DEFAULT '0' NOT NULL,
    uuid_local tinytext,
    uuid_foreign tinytext
);