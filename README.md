## CaRMen
# A Customer Relationship Management system
The foundational thought for this project is to create a CRM system that is ACID and GDPR compliant,

# Build on shoulders of Giants
This project is built on top of many other great projects. As foundation Symfony is used to manage routes, controllers and fundamental features.

As a cookie manager I use : https://silktide.com/consent-manager/install/

# Install
I have not made an installer yet. But insert these menu items as a start.
` 
INSERT INTO `menu_item` (`id`,`name`,`title`,`route`,`menu`,`parent`) VALUES (1,'Menu Items','Menu Items','/menu/item','admin',0), (2,'Hjem','Hjem','/','main',0), (3,'Login','Login','/login','main',2), (4,'Logout','Logout','/logout','main',2), (5,'Register User','Register User','/register','admin',0), (6,'Kundeliste','Kunder','/customer','mainINSERT INTO `menu_item` VALUES',0);
`
sass -w sass/:assets/styles/
`

## migration of test database
php bin/console doctrine:database:create --env=test
php bin/console make:migration --env=test
php bin/console doctrine:migrations:status --env=test
php bin/console doctrine:migrations:migrate --env=test

### This doesnt work
It has generated migration with DEFAULT which is incompatible with sqlite.

## How to update assets
`
bin/console asset-map:compile
`

# Usage of components
## Autocomplete
https://symfony.com/bundles/ux-autocomplete/current/index.html


# Wishlist for features
Users and permissions

Leads - Collect leads from facebook and website.

Customers

Deals - a calendar event

KPI - some sort of ranking on events.

Lead qualification - Rank leads

Reporting - tables and graphs of KPI's

Customerlifecycle - visualization of the customer journey.

Automation - When an enity reaches some state it should be able to trigger some actions.

Email - Templates for standard emails for customers

Department - A user can belong to a department

Teams - Some structure of colaboration between users.

Calendar - scheduled appointments.

Integrations - Zapier, Facebook, mail system.

Internationalization - possibility to have more languages in the user interface.

# Research

Why are CRM faling
https://www.researchgate.net/profile/Mohamed-Tazkarji/publication/340550301_Reasons_for_Failures_of_CRM_Implementations/links/63fe7a9ab1704f343f8d4233/Reasons-for-Failures-of-CRM-Implementations.pdf

# Notes
Error page handling : https://symfony.com/doc/current/controller/error_pages.html

Install profiler : https://symfony.com/doc/current/profiler.html
