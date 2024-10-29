# Changelog

## [Unreleased]
### Fixed
- Fixed English translations of order statuses created by the module
- Fixed HRX block in Order view on Prestashop 1.7.0-1.7.6
- Fixed page title in Prestashop Order page
- Fixed Order status change to "New HRX shipment"
- Fixed getting terminals on Checkout page when their number is very large

### Improved
- Reduced the number of requests to the database when changing the order status
- A parameter has been created in the module settings that allows to limit the terminals displayed on the Checkout page based on the distance from the entered delivery address

## [1.2.3] - Fix track number
### Fixed
- Fixed that the received tracking number of the shipment would be added to the Prestashop Order

## [1.2.2] - Locations getting fix
### Fixed
- Fixed locations list filter in admin HRX Locations page

### Improved
- Increased number of getting terminal locations to 1000 per request

### Updated
- Updated HRX api-lib to 1.0.7

## [1.2.1] - Fresh Install patch
### Fixed
- Fixed an issue when fresh install of module is done it fails to create database tables

## [1.2.0] - Optimum
### Added
- Courier delivery locations synchronization (module settings) and limitations
- Terminal delivery locations synchronization (module settings) and limitations
- BoxPacker by DVDoug to determine if cart products fits within given limits

### Changes
- Changed how costs are controlled, it is now done throug Prestashop -> Carrier settings
- Generated PDF's are either downloaded or openend in browser wihtout saving server side
- Terminal data no longer saved in JSON files

### Fixed
- Fixed missing translatable strings bindings to module
- Various improvements / changes / cleanup

### Updated
- Updated setasign/fpdi to 2.4.1
- Updated setasign/fpdf to 1.86
- Updated terminal-mapping to 1.2.3
- Updated HRX api-lib to 1.0.3 

## [1.0.1] - 2023-07-17
### Fixed
- Fixed mobile phone number get from Order address
- Fixed delivery method recognition
- Changed tracking_number length in database to 32 chars

### Improved
- Added Readme file

## [1.0.0] - 2023-01-13
### Init
- Initial launch of the module
