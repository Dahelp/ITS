-- Delete online chat tables after backup.
-- Run this only after saving/restoring needs are clear.

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `tg_topic_map`;
DROP TABLE IF EXISTS `chat_message`;
DROP TABLE IF EXISTS `chat_operator`;
DROP TABLE IF EXISTS `chat_session`;
DROP TABLE IF EXISTS `chat_settings`;

-- Old/legacy chat tables found in the database and not referenced by current project code.
DROP TABLE IF EXISTS `tblchatclientmessages`;
DROP TABLE IF EXISTS `tblchatgroupmembers`;
DROP TABLE IF EXISTS `tblchatgroupmessages`;
DROP TABLE IF EXISTS `tblchatgroups`;
DROP TABLE IF EXISTS `tblchatgroupsharedfiles`;
DROP TABLE IF EXISTS `tblchatmessages`;
DROP TABLE IF EXISTS `tblchatsettings`;
DROP TABLE IF EXISTS `tblchatsharedfiles`;

SET FOREIGN_KEY_CHECKS=1;
