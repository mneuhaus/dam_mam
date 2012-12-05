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
	tx_dammam_related_files tinytext,
  	tx_dammam_language tinytext,
);

#
# Table structure for table 'tx_dammam_unresolved_relations'
#
CREATE TABLE tx_dammam_unresolved_relations (
    crdate int(11) DEFAULT '0' NOT NULL,
    uuid_local tinytext,
    uuid_foreign tinytext
);