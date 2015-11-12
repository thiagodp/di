# validator

Easy and powerful validation livrary for PHP.

[![Build Status](https://travis-ci.org/thiagodp/validator.svg?branch=master)](https://travis-ci.org/thiagodp/validator)

Current [version](http://semver.org/): `0.4.0` (_not production-ready yet, but usable_)

### Available Rules

- [x] required
- [x] min_length
- [x] max_length
- [x] length_range
- [x] min
- [x] max
- [x] range
- [x] regex
- [x] format
- [x] custom (you can add others easily)

### Available Formats

- [x] anything
- [x] name
- [x] word
- [x] alphanumeric*
- [x] alpha only*
- [x] ascii*
- [x] numeric
- [x] integer
- [x] price
- [x] tax
- [x] date_dmy
- [x] date_mdy
- [x] date_ymd
- [x] date_dmy_dotted
- [x] date_mdy dotted
- [x] date_ymd dotted
- [x] date_dmy_dashed
- [x] date_mdy dashed
- [x] date_ymd dashed
- [x] date**
- [x] time**
- [x] longtime**
- [x] datetime**
- [x] longdatetime**
- [x] email
- [x] http
- [x] url
- [x] ip
- [x] ipv4
- [x] ipv6
- [x] custom (you can add others easily)

_\* Not fully tested, but it should work._

_** Not fully tested, and it will change soon._

### More

- [x] Error messages can be specified by locale.
- [x] Error messages can be specified at once, allowing, for example, reading them from a JSON file.
- [x] Formats can be specified by locale.
- [x] Formats can be specified at once, allowing, for example, reading them from a JSON file.
- [x] Formats can be specified without having to extend any class.
- [x] Rules can be specified without having to extend any class.
- [x] Classes use [fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) (you type less).
- [ ] Builder classes available.
 
