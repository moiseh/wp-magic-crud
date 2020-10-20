# Magic admin CRUD plugin for WordPress

This plugin allows a developer to create fast CRUD's for WordPress admin panel. It was made for the author current CRUD needs, that may (an probably) be very different of yours.

## Overview

* It's made to use with custom business database tables, does not use WP Post, Tags nor media library and similar WP API stuff
* It's made to create generic CRUD's with little effort, priorizing convention over configuration. Not recommended to create complex admin panels
* Generates automatically the database structure (can be controlled or deactivated, the example repository)
* Uses `WP_List_Table` WordPress API to create the listings, so the result will be similar of the WordPress Posts and Pages admin sections
* Stuff included: Menus, Actions, Bulk Actions, Generic search, Pagination, Validation, Complex relationship fields

## Installation Steps

### 1. Require the Package

Add **wp-magic-crud** plugin package inside your **wordpress/wp-content/plugins** folder with the following command:

```bash
git clone https://github.com/moiseh/wp-magic-crud.git
```

Alternativately you can download using the following link: https://github.com/moiseh/wp-magic-crud/archive/master.zip

### 2. Enable this plugin in your WordPress admin panel

## Built-in field types

* text
* textarea
* email
* integer
* select
* belongs_to (references another entity)
* one_to_many (creates a repeatable table linking to multiple entity)
* has_many (creates multi-selectable checkboxes)
* checkbox_multi
* datetime
* boolean

## How to use

You can implement your custom plugin to create CRUD entities or just use it as a library embed in your plugin.
A full example of how to use can be found in the following repository: https://github.com/moiseh/wpmc-example.git

The first thing to learn is how declare Entities and Fields reading the [main example plugin file](https://github.com/moiseh/wpmc-example/blob/master/wpmc-example.php).

## F.A.Q.

### 1. Why another admin CRUD builder?

I didn't found any ready to use solution that fit exactly my needs. With this i can use or embeed a lightweight API to generate CRUDs in my another plugins or create some SAAS application.

### 2. Why using so many arrays and not more PHP objects instead to declare entities?

With array to define the Entities and Fields you have much more flexibility, for example, to build a bridge and read it dynamically from some MySQL database or JSON files. It allows to do whatever you want and reduce the complexity in learning how create the Entity objects.

### 3. This is a ready to use for production?

It was working and tested to use for my personal plugins, but is not heavily tested and backwards compatibility is not guaranteed. You can fork the project if it's useful to you and modify whatever you want. This was initially inspired in [WP Basic Crud](https://wordpress.org/plugins/wp-basic-crud/) plugin.
