# Changelog

## [1.2.0] - 2022-07-20

### Added
- added NO API Mode, when terminal list will be passed localy instead of API
- added ability to set custom CSS class with theme variables. See `src/styles/main.css` `.tmjs-default-theme` rule
- added new function to add custom marker Icons
- ability to change both tile map url and map data attribution by passing `customTileServerUrl` and `customTileAttribution`

## [1.1.3] - 2022-01-24

### Improved
- show empty string instead of name in terminals list if name is null

## [1.1.2] - 2021-12-31

### Added
- added new parameter `receiver-address` - to pass full address into api for limited range terminal selection

### Improved
- Updated readme

### Updated
- Build tools

## [1.1.1] - 2021-06-16

### Added
- Modal open information event - `modal-opened`
- Modal close information event - `modal-closed`
- New parameters `city` and `postal_code` for initializing, this way API should limit terminal list, if left empty or not supplied, works like before

### Fixed
- Default translation for container text

### Improved
- Modal is now hidden by moving it offscreen instead of display: none, this saves on browser repaint time

## [1.1.0] - 2020-10-09

### Added
- Ability to set map in targeted element
- Ability to disable modal (instead show as flat element)
- Ability to hide main selection container
- Ability to hide "Select" button in map terminal list

### Improved
- Better smooth scrolling to selected terminal in list (sorry safari not supported)
- Improved README.md

### Fixed
- Map marker tooltip to show address if there is no comment in terminal data

## [1.0.2] - 2020-10-02

### Improved
- Overall terminal list styling improvements
- Closer zoom in for geolocation search result

### Fixed
- Fixed geolocation loader showing too early

## [1.0.1] - 2020-10-01
### Fixed
- Fixed data element detection for firefox (now using Event.target instead of Event.path)