# Magic admin CRUD plugin for WordPress

This plugin allows a developer to create fast CRUD's for WordPress admin panel. It was made for the author current CRUD needs, that may (an probably) be very different of yours.

## Overview

* It's made to use with custom business database tables, does not use WP Post, Tags nor media library and similar WP API stuff
* It's made to create generic CRUD's with little effort, priorizing convention over configuration. Not recommended to create complex admin panels
* Generates automatically the database structure (can be deactivated, see *Overriding* section)
* Uses `WP_List_Table` WordPress API to create the listings, so the result will be similar of the WordPress Posts and Pages admin sections
* Optionally allow to filter by the current logged user for each entity / CRUD. May
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

You can implement your custom plugin to create CRUD entities.
A full example of how to use can be found in the following repository: https://github.com/moiseh/wpmc-example.git

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

## F.A.Q.

### 1. Why another admin CRUD builder?

I don't found any ready to use solution that fit exactly my needs. With this i can use or embeed a lightweight API to generate CRUDs in my another plugins or create some SAAS application. I also like to do it for the fun and deal with CRUDs.

### 2. Why using so many arrays and not more PHP objects instead?

With array to define the Entities and Fields you have much more flexibility, for example, to build a bridge and read it dynamically from some MySQL database or JSON files. It allows to do whatever you want and reduce the complexity in learning how create the Entity objects.

### 3. This is a ready to use for production?

No it's more like an experimental and hobbyist project. You can fork the project if it's useful to you and modify whatever you want. This was initially inspired in [WP Basic Crud](https://wordpress.org/plugins/wp-basic-crud/) plugin.