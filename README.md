CategoryFilter
==============

[![Build Status](https://travis-ci.com/atk14/CategoryFilter.svg?branch=master)](https://travis-ci.com/atk14/CategoryFilter)

Usage
-----

TODO

Installation
------------

    composer require atk14/category-filter

    ln -s ../../vendor/atk14/category-filter/src/app/forms/filter_form.php app/forms/filter_form.php
    ln -s ../../vendor/atk14/category-filter/src/app/fields/filter_bool_field.php app/fields/filter_bool_field.php
    ln -s ../../vendor/atk14/category-filter/src/app/fields/filter_multiple_choice_field.php app/fields/filter_multiple_choice_field.php
    ln -s ../../vendor/atk14/category-filter/src/app/fields/filter_range_field.php app/fields/filter_range_field.php
    ln -s ../../vendor/atk14/category-filter/src/app/widgets/filter_checkbox_select_multiple.php app/widgets/filter_checkbox_select_multiple.php
    ln -s ../../vendor/atk14/category-filter/src/public/scripts/filter.js public/scripts/filter.js



Testing
-------

    composer update --dev

Run tests:

    cd test
    ../vendor/bin/run_unit_tests

License
-------

Filter is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)

[//]: # ( vim: set ts=2 et: )
