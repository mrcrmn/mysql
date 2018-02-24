# MySQL
A PDO wrapper for easy and intuitive MySQL database interaction.

### Installation
To install run `composer require mrcrmn/mysql`  
To use create a new Database object and connect it to the MySQL database.
```php
$db = new Mrcrmn\Mysql\Database;
$db->connect($host, $user, $password, $port, $dbname);
```

### Usage
This library support 4 basic actions for interaction with the database: "INSERT", "SELECT", "UPDATE", "DELETE".

#### INSERT
//TODO  

#### SELECT
Example:  
```php
$db->select('firstname', 'lastname')->from('customers')->where('lastname', 'smith')->get();
```
This returns the query result as an assoc array.
##### Available Methods
// TODO  

#### UPDATE
//TODO  

#### DELETE  
//TODO  
