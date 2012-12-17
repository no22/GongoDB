GongoDB
================================================================
GongoDB is a SQL friendly ORM for PHP 5 that has named_scope and fluent query builder.  
It assembles a query by combining named_scope as a fragment of SQL.  
It is suitable for building complicated SQL dynamically.  


Setup
----------------------------------------------------------------
GongoDB uses PDO extension to connect databases.

    include "gongo.php";
    $pdo = new PDO("mysql:host=localhost;dbname=dbname", "user", "password");
    $db = new Gongo_Db($pdo);


Define Data Mapper
------------------------------------------------------------------------
Named_scope is defined in a data mapper class.

    class Mapper_Book extends Gongo_Db_Mapper
    {
        protected $table = "books";
        protected $namedScopes = array(
            'bookProperty' => array(
                'select' => array('book.id AS id', 'book.title AS title'),
            ),
            'bookAuthor' => array(
                'bookProperty',                     // reuse of named_scope
                'select' => array('author.name AS author_name'),
                'join' => array('author ON author.id = author_id'),
                'entityclass' => 'Model_Book',      // named_scope can have entity class in each.
            ),
            'byAuthorName' => array(
                'where' => array('author.name = :name'),
                'params' => ':name', // order of a named parameter
            ),
            'findByAuthorName' => array(
                'bookAuthor', 'byAuthorName',
            ),
        );
    }


Define Entity Class (Model Class)
------------------------------------------------------------------------
Named_scope can have entity class in each.  
Gongo_Bean is used as entity class when not specifying an entity class in data mapper class.

    class Model_Book extends Gongo_Bean
    {
        function toString()
        {
            return "Title:{$this->title} Author：{$this->author_name}";
        }
    }


Load an Entity by Primary Key
------------------------------------------------------------------------

    $bookMapper = $db->Book;        // instantiate mapper class
    $book = $bookMapper->get(1);    // load an entity


Save an Entity
------------------------------------------------------------------------

    $authorMapper = $db->Author;    // instantiate mapper class
    $author = $authorMapper->get(); // instantiate empty entity
    $author->name = "William Gibson";
    $authorMapper->save($author);   // save an entity


Find an Entity by using named_scope
------------------------------------------------------------------------

    $book = $bookMapper->q()->findByAuthorName->first('William Gibson');
    // chain of named_scopes
    $book = $bookMapper->q()->bookAuthor->byAuthorName->first('William Gibson');


Find all Entities by using named_scope
------------------------------------------------------------------------

    // $books = $bookMapper->q()->findByAuthorName->all('William Gibson');
    // 'all' method is omissible
    $books = $bookMapper->q()->findByAuthorName('William Gibson');
    // result object is a lazy iterator so sql is not executed yet
    foreach ($books as $book) {
        // sql is executed when iteration has started
        echo $book->toString() . "\n";
    }


LIMIT & ORDER
------------------------------------------------------------------------

    $books = $bookMapper->q()->findByAuthorName->limit(10)->orderBy('title ASC')->all('William Gibson');

You may replace an order. 

    $books = $bookMapper->q()->limit(10)->orderBy('title ASC')->findByAuthorName('William Gibson');


SubQuery
------------------------------------------------------------------------

### Nested Definition ###

    class Mapper_Departments extends Gongo_Db_Mapper
    {
        protected $table = "departments";
        protected $namedScopes = array(
            'findDepartmentsByEmployeesAge' => array(
                'where' => array(
                    '#id IN' => array(
                        // nested definition in named_scope
                        'select' => array('department_id'),
                        'from' => array('employees'),
                        'where' => array('age = :age'),
                    ),
                ),
                'params' => ':age',
            ),
        );
    }


### Using another scope ###

    class Mapper_Departments extends Gongo_Db_Mapper
    {
        protected $table = "departments";
        protected $namedScopes = array(
            'findByEmployeesAge' => array(
                // using another named_scope
                'where' => array('#id IN' => array('departmentIdsByEmployeesAge')),
                'params' => ':age',
            ),
            'departmentIdsByEmployeesAge' => array(
                'select' => array('department_id'),
                'from' => array('employees'),
                'where' => array('age = :age'),
            ),
        );
    }


"AND" and "OR" operators in WHERE clause
------------------------------------------------------------------------

    class Mapper_Employees extends Gongo_Db_Mapper
    {
        protected $table = "employees";
        protected $namedScopes = array(
            'ageAndSex' => array(
                'where' => array('age = :age','sex = :sex'),
    //          'where' => array(array('$and' => array('age = :age','sex = :sex'))),
            ),
            'ageOrSex' => array(
                'where' => array(array('$or' => array('age = :age','sex = :sex'))),
            ),
        );
    }


License
------------------------------------------------------------------------
GongoDB is dual Licensed MIT and GPLv3. You may choose the license that fits best for your project.

What is Gongo?
------------------------------------------------------------------------
* "Gongo" means a ["Kappa"][1] in the dialect of the Tsuyama region at Okayama prefecture in Japan. 
* There is no relationship with MongoDB.

[1]:http://en.wikipedia.org/wiki/Kappa_(folklore)
