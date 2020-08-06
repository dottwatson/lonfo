# Lonfo
traverse an array by accessing the children and parents of a node

```php

$data = [
    'bar' => [0,1,2,3,4,5],
    'foo' => [10,20,30,40,50]
];


```
with dedicated function

```php

$array = lonfo($data); //returns a Lonfo\Walker Object

```
or with instance

```php

use Lonfo\Walker;

$array = new Walker($data); //returns a Lonfo\Walker Object

```
then traverse your array

```php

$item = $array->get('bar'); //returns a Lonfo\Walker Object

$itemData = $array->get('bar')->value(); //returns  [0,1,2,3,4,5]

//retrieve a value and back to its parent

$item = $array->get('bar'); //returns a Lonfo\Walker Object
$parentNode = $item->parent(); //returns a Lonfo\Walker Object of the parent array

//cycle items in array
while($item = $array->next()){
   print_r($item->value());
}

//rewind pointer
$array->rewind();

```

accessing to values

```php

$value = $array->get('foo')->get(2);
echo $value->value(); //returns 30

$parent = $value->parent()->value(); // returns  [10,20,30,40,50]


$result = $array
    ->nthChild(2)   //select child #2
    ->append(1000)  //add 1000
    ->parent()      //return top  of proviouse hthChild
    ->set('genders',['Male','Female','Unisex']) //add key=>value
    ->nthChild(1)   //select child #1
    ->set(6,60)     // add key=>value
    ->parent()      // return top of proviouse hthChild
    ->value();       //get value

// result = Array
// (
//     [bar] => Array
//         (
//             [0] => 0
//             [1] => 1
//             [2] => 2
//             [3] => 3
//             [4] => 4
//             [5] => 5
//             [6] => 60
//         )

//     [foo] => Array
//         (
//             [0] => 10
//             [1] => 20
//             [2] => 30
//             [3] => 40
//             [4] => 50
//             [5] => 1000
//         )

//     [genders] => Array
//         (
//             [0] => Male
//             [1] => Female
//             [2] => Unisex
//         )

// )

echo $array->get('bar')->get(2)->xpath(); // returns "bar/2"

```
## Available methods on array

Here a list on the available methods on `Lonfo\Walker`

| Method | Description | Options | Notes |
|--------|-------------|---------|-------|
| `get` | Get an item by its key | *string* $key | |
| `nthChild` | Retrieve a child from its numeric index or a closure. The childs counters starts from 1. If $keyNumber is a Closure, the other extra parameters will be sent to the closure  |  *int \| Closure* $keyNumber | |
| `items` | Get indexed items in current array | | |
| `count` | Returns the array items count | | |
| `keys` | Returns the array keys | | |
| `value` |  Return the current array data with all modifications | | |
| `parent` | Returns the parent node in the array if any | | |
| `has` | Check if key exists in array | *string* $key | |
| `prev` | Move internal pointer to the previouse item and returns it if exists | | |
| `next` | Move internal pointer to the previouse item and returns it if exists | | |
| `key` | Returns the current item key | | |
| `rewind` | Reset array internal pointer | | |
| `first` | Returns the first item in the array if exists | | |
| `last` | Returns the last item in the array if exists | | |
| `shift` | Remove the first item in the array if exists and returns it | | |
| `pop` | Remove the last item in the array if exists and returns it | | |
| `set` | Set a pair key => value item in teh array. If exists, will be overwritten | *string* $key, *string* $value | used to add/overwrite items |
| `append` | Append alle items, passed as arguments, to the array | [$arg1,[$arg2]...] | |
| `prepend` | Prepends all items, passed as arguments, to the array | [$arg1,[$arg2]...] | |
| `xpath` | Returns the full data path , included current key  | [*string* $separator = '/'] | |
| `iterable` | Tells if is a valid array (a traversable Walker) | | This is useful for determinate if an end value or array to traverse |


## Available methods on Value


Here a list on the available methods on `Lonfo\Value`

| Method | Description | Options | Notes |
|--------|-------------|---------|-------|
| `key` | Returns the current item key | | |
| `value` | Returns value | | |
| `parent` | Returns the parent array | | |
| `type` | Returns the value type | | |
| `xpath` | Returns the full data path , included current key  | [*string* $separator = '/'] | |
| `iterable` | Tells if is a valid array (a traversable Walker) | | This is useful for determinate if an end value or array to traverse |
