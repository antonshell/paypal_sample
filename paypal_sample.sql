/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50616
Source Host           : localhost:3306
Source Database       : paypal_sample

Target Server Type    : MYSQL
Target Server Version : 50616
File Encoding         : 65001

Date: 2015-08-13 01:24:34
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `extra_services`
-- ----------------------------
DROP TABLE IF EXISTS `extra_services`;
CREATE TABLE `extra_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider` varchar(45) DEFAULT NULL,
  `service_name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of extra_services
-- ----------------------------
INSERT INTO `extra_services` VALUES ('1', 'twilio', 'textMessages');

-- ----------------------------
-- Table structure for `extra_services_prices`
-- ----------------------------
DROP TABLE IF EXISTS `extra_services_prices`;
CREATE TABLE `extra_services_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) DEFAULT NULL,
  `count_greater` int(11) DEFAULT NULL,
  `currency` varchar(45) DEFAULT NULL,
  `price` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_extra_services_prices_service_id_idx` (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of extra_services_prices
-- ----------------------------
INSERT INTO `extra_services_prices` VALUES ('1', '1', '50', 'usd', '0.5');
INSERT INTO `extra_services_prices` VALUES ('2', '1', '100', 'usd', '0.25');
INSERT INTO `extra_services_prices` VALUES ('3', '1', '1000', 'usd', '0.15');
INSERT INTO `extra_services_prices` VALUES ('4', '1', '0', 'usd', '0.55');

-- ----------------------------
-- Table structure for `extra_services_users`
-- ----------------------------
DROP TABLE IF EXISTS `extra_services_users`;
CREATE TABLE `extra_services_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `items_count` varchar(11) DEFAULT NULL,
  `permissions` varchar(50) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_users_extra_services_idx` (`user_id`),
  KEY `FK_users_extra_services_service_id_idx` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of extra_services_users
-- ----------------------------

-- ----------------------------
-- Table structure for `paypal_log`
-- ----------------------------
DROP TABLE IF EXISTS `paypal_log`;
CREATE TABLE `paypal_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_id` varchar(45) DEFAULT NULL,
  `subscr_id` varchar(45) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created` varchar(45) DEFAULT NULL,
  `message` text,
  `data` text,
  `level` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_paypal_log_users_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of paypal_log
-- ----------------------------

-- ----------------------------
-- Table structure for `paypal_transactions`
-- ----------------------------
DROP TABLE IF EXISTS `paypal_transactions`;
CREATE TABLE `paypal_transactions` (
  `txn_id` varchar(255) NOT NULL,
  `txn_type` varchar(45) DEFAULT NULL,
  `mc_gross` varchar(45) DEFAULT NULL,
  `mc_currency` varchar(45) DEFAULT NULL,
  `quantity` varchar(45) DEFAULT NULL,
  `payment_date` varchar(255) DEFAULT NULL,
  `payment_status` varchar(45) DEFAULT NULL,
  `business` varchar(45) DEFAULT NULL,
  `receiver_email` varchar(45) DEFAULT NULL,
  `payer_id` varchar(45) DEFAULT NULL,
  `payer_email` varchar(45) DEFAULT NULL,
  `relation_id` int(11) DEFAULT NULL,
  `relation_type` varchar(45) DEFAULT NULL,
  `created_date` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`txn_id`),
  UNIQUE KEY `txn_id_UNIQUE` (`txn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of paypal_transactions
-- ----------------------------

-- ----------------------------
-- Table structure for `subscriptions`
-- ----------------------------
DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `subscription_id` varchar(45) DEFAULT NULL,
  `permission` varchar(45) DEFAULT NULL,
  `created_date` varchar(45) DEFAULT NULL,
  `updated_date` varchar(45) DEFAULT NULL,
  `payment_date` varchar(45) DEFAULT NULL,
  `items_count` int(11) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of subscriptions
-- ----------------------------

-- ----------------------------
-- Table structure for `subscription_plans`
-- ----------------------------
DROP TABLE IF EXISTS `subscription_plans`;
CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider` varchar(45) DEFAULT NULL,
  `service_name` varchar(45) DEFAULT NULL,
  `price` varchar(45) DEFAULT NULL,
  `price_type` varchar(45) DEFAULT NULL,
  `period` varchar(45) DEFAULT NULL,
  `is_active` varchar(45) DEFAULT NULL,
  `sortorder` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of subscription_plans
-- ----------------------------
INSERT INTO `subscription_plans` VALUES ('1', 'getscorecard', 'pro', '5.00', 'user', 'month', '1', '2');
INSERT INTO `subscription_plans` VALUES ('2', 'getscorecard', 'enterprise', '', 'user', 'month', '1', '3');
INSERT INTO `subscription_plans` VALUES ('3', 'getscorecard', 'free', '0.00', 'user', 'month', '1', '1');

-- ----------------------------
-- Table structure for `subscription_plan_options`
-- ----------------------------
DROP TABLE IF EXISTS `subscription_plan_options`;
CREATE TABLE `subscription_plan_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `value` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_subscription_plan_options_plan_id_idx` (`plan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of subscription_plan_options
-- ----------------------------
INSERT INTO `subscription_plan_options` VALUES ('1', '1', 'users', '-1');
INSERT INTO `subscription_plan_options` VALUES ('2', '1', 'companies', '100');
INSERT INTO `subscription_plan_options` VALUES ('3', '1', 'people', '100');
INSERT INTO `subscription_plan_options` VALUES ('4', '1', 'tasks', '100');
INSERT INTO `subscription_plan_options` VALUES ('5', '1', 'products', '100');
INSERT INTO `subscription_plan_options` VALUES ('6', '1', 'opportunities', '100');
INSERT INTO `subscription_plan_options` VALUES ('7', '1', 'leads', '100');
INSERT INTO `subscription_plan_options` VALUES ('8', '1', 'files', '100');
INSERT INTO `subscription_plan_options` VALUES ('9', '1', 'teams', '-1');
INSERT INTO `subscription_plan_options` VALUES ('10', '1', 'territories', '-1');
INSERT INTO `subscription_plan_options` VALUES ('11', '1', '3rdPartyGoogle', '-1');
INSERT INTO `subscription_plan_options` VALUES ('12', '1', 'customization', '-1');
INSERT INTO `subscription_plan_options` VALUES ('13', '1', 'accessManager', '-1');
INSERT INTO `subscription_plan_options` VALUES ('14', '1', 'reports', '-1');
INSERT INTO `subscription_plan_options` VALUES ('15', '1', 'salesLifecycle', '-1');
INSERT INTO `subscription_plan_options` VALUES ('16', '1', 'healthScore', '-1');
INSERT INTO `subscription_plan_options` VALUES ('17', '1', 'netPromoter', '-1');
INSERT INTO `subscription_plan_options` VALUES ('18', '1', 'dashboard', '-1');
INSERT INTO `subscription_plan_options` VALUES ('19', '1', 'targetSettings', '-1');
INSERT INTO `subscription_plan_options` VALUES ('20', '1', 'taskAutomation', '-1');
INSERT INTO `subscription_plan_options` VALUES ('21', '1', 'commissionSettings', '-1');
INSERT INTO `subscription_plan_options` VALUES ('22', '2', 'users', '-1');
INSERT INTO `subscription_plan_options` VALUES ('23', '2', 'companies', '-1');
INSERT INTO `subscription_plan_options` VALUES ('24', '2', 'people', '-1');
INSERT INTO `subscription_plan_options` VALUES ('25', '2', 'tasks', '-1');
INSERT INTO `subscription_plan_options` VALUES ('26', '2', 'products', '-1');
INSERT INTO `subscription_plan_options` VALUES ('27', '2', 'opportunities', '-1');
INSERT INTO `subscription_plan_options` VALUES ('28', '2', 'leads', '-1');
INSERT INTO `subscription_plan_options` VALUES ('29', '2', 'files', '-1');
INSERT INTO `subscription_plan_options` VALUES ('30', '2', 'teams', '-1');
INSERT INTO `subscription_plan_options` VALUES ('31', '2', 'territories', '-1');
INSERT INTO `subscription_plan_options` VALUES ('32', '2', '3rdPartyGoogle', '-1');
INSERT INTO `subscription_plan_options` VALUES ('33', '2', 'customization', '-1');
INSERT INTO `subscription_plan_options` VALUES ('34', '2', 'accessManager', '-1');
INSERT INTO `subscription_plan_options` VALUES ('35', '2', 'reports', '-1');
INSERT INTO `subscription_plan_options` VALUES ('36', '2', 'salesLifecycle', '-1');
INSERT INTO `subscription_plan_options` VALUES ('37', '2', 'healthScore', '-1');
INSERT INTO `subscription_plan_options` VALUES ('38', '2', 'netPromoter', '-1');
INSERT INTO `subscription_plan_options` VALUES ('39', '2', 'dashboard', '-1');
INSERT INTO `subscription_plan_options` VALUES ('40', '2', 'targetSettings', '-1');
INSERT INTO `subscription_plan_options` VALUES ('41', '2', 'taskAutomation', '-1');
INSERT INTO `subscription_plan_options` VALUES ('42', '2', 'commissionSettings', '-1');
INSERT INTO `subscription_plan_options` VALUES ('43', '3', 'users', '2');
INSERT INTO `subscription_plan_options` VALUES ('44', '3', 'companies', '100');
INSERT INTO `subscription_plan_options` VALUES ('45', '3', 'people', '100');
INSERT INTO `subscription_plan_options` VALUES ('46', '3', 'tasks', '100');
INSERT INTO `subscription_plan_options` VALUES ('47', '3', 'products', '100');
INSERT INTO `subscription_plan_options` VALUES ('48', '3', 'opportunities', '100');
INSERT INTO `subscription_plan_options` VALUES ('49', '3', 'leads', '100');
INSERT INTO `subscription_plan_options` VALUES ('50', '3', 'files', '100');
INSERT INTO `subscription_plan_options` VALUES ('51', '3', 'teams', '0');
INSERT INTO `subscription_plan_options` VALUES ('52', '3', 'territories', '0');
INSERT INTO `subscription_plan_options` VALUES ('53', '3', '3rdPartyGoogle', '-1');
INSERT INTO `subscription_plan_options` VALUES ('54', '3', 'customization', '0');
INSERT INTO `subscription_plan_options` VALUES ('55', '3', 'accessManager', '0');
INSERT INTO `subscription_plan_options` VALUES ('56', '3', 'reports', '-1');
INSERT INTO `subscription_plan_options` VALUES ('57', '3', 'salesLifecycle', '0');
INSERT INTO `subscription_plan_options` VALUES ('58', '3', 'healthScore', '0');
INSERT INTO `subscription_plan_options` VALUES ('59', '3', 'netPromoter', '0');
INSERT INTO `subscription_plan_options` VALUES ('60', '3', 'dashboard', '-1');
INSERT INTO `subscription_plan_options` VALUES ('61', '3', 'targetSettings', '0');
INSERT INTO `subscription_plan_options` VALUES ('62', '3', 'taskAutomation', '0');
INSERT INTO `subscription_plan_options` VALUES ('63', '3', 'commissionSettings', '0');

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(45) DEFAULT NULL,
  `lastname` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of users
-- ----------------------------
