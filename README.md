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
```php
$db->table('my_table')->insert([
   'foo' => 'bar',
   'baz' => 'foo'
]);
```

#### SELECT
Example:  
```php
$db->select('firstname as fn', 'lastname as ln')->from('customers')->where('lastname', 'smith')->get();
```
This returns the query result as an assoc array.
##### Available Methods
```php
$db->select(); // default = *
$db->select(['column_1', 'column_2']); // You may also just add as many arguments as you like without the array.
$db->into('table_name'); // Sets the table name.
$db->where('column_name', 'operator', 'value'); // If you don't pass the operator it defaults to '='.
$db->orWhere('column_name', 'operator', 'value'); // Same as a where, but with the OR before it.
$db->whereIn('column_name', ['value_1', 'value_2']); // Adds a where in subquery.
$db->orWhereIn('column_name', ['value_1', 'value_2']); // Take a guess.
$db->join('table_name', 'foreign_column_name', 'local_column_name'); // the local column name defaults to 'id'
$db->leftJoin('table_name', 'foreign_column_name', 'local_column_name');
$db->rightJoin('table_name', 'foreign_column_name', 'local_column_name');
$db->orderBy('column_name', 'ASC');
$db->groupBy('column_name');
$db->limit(1);
$db->offset(8);
$db->get(); // Executes the query and returns the result as an array.
$db->first(); // Gets the first entry.
$db->count(); // Gets the number of rows in the result.
$db->getQuery(); // Returns the built query as a string.
```

#### UPDATE
```php
$db
    ->table('my_table')
    ->where('foo', 'baz')
    ->update([
       'foo' => 'bar',
       'baz' => 'foo'
    ]);
```

#### DELETE  
```php
$db
    ->table('my_table')
    ->where('foo', 'baz')
    ->delete();
```
