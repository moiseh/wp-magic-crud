# Magic admin CRUD plugin for WordPress

This plugin allows a developer to create fast CRUD's for WordPress admin panel. It was made for the author current CRUD needs, that can (an probably) be very different of yours.

**This plugin is not ready to use**

## Overview

* It's made to use with custom business database tables, does not use WP Post, Tags and/or media library
* It's was not made to create complex admin panels, instead the idea is to create generic CRUD's with little effort (convention over configuration)
* Generates automatically the database structure (can be deactivated, see *Overriding* section)
* Uses `WP_List_Table` WordPress API to create the listings, so the result will be similar of the WordPress Posts and Pages admin sections
* Allow to filter by the current logged user for each entity / CRUD
* Stuff and APIs included: Actions, Bulk Actions, Generic search, Pagination, Validation

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
* belongs_to (references another entity)
* has_many (creates a repeatable table linking to another entity)

## How to use

You can implement your custom plugin to create CRUD entities. An example can be found in the following repository:
https://github.com/moiseh/wp-magic-crud-example.git

### 1. Creating entities

You can implement the CRUD entities in your custom plugin.
By default, every entity have their custom table in database.

```php
add_action('wpmc_entities', function($entities){
    $entities['Contact'] = [
        'table_name' => 'sys_contacts',
        'default_order' => 'name',
        'display_field' => 'name',
        'singular' => 'Contact',
        'plural' => 'Contacts',
        'restrict_logged' => 'user_id',
        'fields' = [
            'name' => [
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'flags' => ['list','sort','view','create','update'],
            ],
            'student_id' => [
                'label' => 'Estudante',
                'type' => 'belongs_to',
                'ref_entity' => 'Student',
                'required' => true,
                'flags' => ['list','sort','view','create','update'],
            ],
            'lastname' => [
                'label' => 'Segundo nome',
                'type' => 'text',
                'flags' => ['list','sort','view','create','update'],
            ],
            'email' => [
                'label' => 'E-mail',
                'type' => 'email',
                'flags' => ['list','sort','view','create','update'],
            ],
            'phone' => [
                'label' => 'Telefone',
                'type' => 'integer',
                'flags' => ['list','sort','view','create','update'],
            ],
            'cellphone' => [
                'label' => 'Celular',
                'type' => 'text',
                'flags' => ['list','sort','view','create','update'],
            ],
        ];
    );

    $entities['Student'] = array(
        'table_name' => 'sys_students',
        'default_order' => 'name',
        'display_field' => 'name',
        'singular' => 'Student',
        'plural' => 'Students',
        'restrict_logged' => 'user_id',
        'fields' = [
            'name' => [
                'label' => 'Nome',
                'type' => 'text',
                'required' => true,
                'flags' => ['list','sort','view','create','update'],
            ],
            'lastname' => [
                'label' => 'Segundo nome',
                'type' => 'text',
                'flags' => ['list','sort','view','create','update'],
            ],
            'email' => [
                'label' => 'E-mail',
                'type' => 'email',
                'flags' => ['list','sort','view','create','update'],
            ],
            'contacts' => [
                'label' => 'Contatos',
                'type' => 'has_many',
                'ref_entity' => 'Contact',
                'ref_column' => 'student_id',
                'flags' => ['list','sort','view','create','update'],
            ],
        ]
    );

    return $entities;
});
```

### 2. Overriding

By default the plugin tries to execute **CREATE TABLE** and/or change fields at runtime when the **fields** structure changes in the entities array, using WordPress `dbDelta()` function. If you want to disable this to have full control over database or by performance reasons, it's possible by doing:

```php
add_action('wpmc_create_tables', '__return_false', 10, 2);
```