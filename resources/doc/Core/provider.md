Provider and Repository Interface and method naming
======================================

A provider is basically nothing more than a "get a value for key" class. This
makes it somewhat equal to the offsetGet() method of Storage.

A Provider is the minimal interface for classes that just have to deliver
and not to persist anything. In most use cases you only need a provider.

The first and most important thing for me is that you write your own repository
interfaces. If you use an ems class, hide it behind your own repository. This
guideline is how we write repositories and a big explanation why.
So if you add code to ems/cmsable or any other lib I work on you must follow this
conventions too.

As in the original repository pattern as [described by Martin Fowler](https://martinfowler.com/eaaCatalog/repository.html)
the interface of an repository should be as working with a collection.
(_"A Repository mediates between the domain and data mapping layers, acting like an in-memory domain object collection"_)
To read data should be like working with an object collection in the programming language.
In PHP, in opposite to python, java and other languages, a plain collection does
not exist. It would be an array.
So the repository methods could be:

(ArrayAccess)
* ->offsetExist()
* ->offsetGet($offset)
* ->offsetSet($offset, $value)
* ->offsetUnset($offset)

Unfortunately there is no ->offsetPush($value) method or a guaranteed numeric
indexed version.
We only have [ArrayObject::append()](https://www.php.net/manual/de/arrayobject.append.php)
that is somehow what is normally needed here to add objects to the collection.
I also do not like the word "offset" in it. It sounds bound to array keys.

At the end php has no native collection but this document is more about
naming and implementing providers and repositories not about PHP itself.

In most use cases we care about CRUD.

* Create:   Add to a collection
* Retrieve: Read from a collection (find one or many items, count all, ...)
* Update:   Change one object of a collection
* Delete:   Remove an item from a collection

Read would be divided in three categories:

Get an item by its unique identifier (or multiple, secondary unique identifiers).
Get all items of a collection.
Count the collection

Due to the fact we mostly read from big data storages we cannot make anything in
memory so array_filter($orderRepository->all(), function () {}) is a bad idea.
Therefore, the usage and naming should be as much as it gets like internal collections
but working with them exactly the same is mostly not possible.

So we have the "Software Architecture Viewpoint" telling us to work like collections
and the "Implementation Viewpoint" forcing us to work a little different.

So we mostly have to add this three actions for reading:

* Get all items of this collection (by a traversable to not explode memory)
* Find a subset of all items (By a filter and offset/limit to not explode memory)
* Count items (To take decisions and build a paginator, also of subsets)

Aggregations other than count (min,max,avg) I would not consider as they are not
returning items if this collection (objects). 
They could also land in your repository but for me, it is no basic feature.


Regarding [this great Answer](https://stackoverflow.com/questions/2141818/method-names-for-getting-data)
the following naming conventions are used in ems:

Retrieve one uniquely addressable item (get)
--------------------------------------------

All methods with a get prefix imply at first that the criteria is totally clear
and this method will always return the same result.

In Laravel Eloquent this is named find($id)
In ReadBeanPHP this is named load($entity, $id)
In Doctrine it is named find($entity, $id)
In Propel it is named findPk($id)
In sqlalchemy (python) it is get($id)
In django orm (python) it is also get($id)

When taking Fowlers definition PHP has this magic methods:

__get($key)
offsetGet($offset)

PSR Interfaces:
Psr\Container\ContainerInterface::get($id)
Psr\SimpleCache\CacheInterface::get($key, $default=null)

And in general I would say you access a collection/storage with:

get($key) -> item $key of collection
set($key, $item) -> set $item into $collection

(Like in python dictionaries)

So I am very wondering why PHP orms are taking find($id) as the method of choice.
It also does not align with this stack overflow argumentation. (They added a plural
to every method name, so it is not 100% comparable)

So in my opinion get($id) will be used to get the item by its obviously known
single main unique key.

Getting items by another unique key will be named getByLogin() / getByPath() ...
But it has to be clear that the passed argument has to be unique for all objects.

Retrieve all stored items (all())
------------------------------------------
Initially ems just used \Traversable for the repository itself to return an
iterator returning all items. But this is IDE unfriendly and for programmers that
are not used to iterators and generators less obvious.
So implement an all() method that returns an iterator but is noted to return
itemClass[] in phpdoc.

Retrieve a subset of all items (findBy)
------------------------------------------
All methods with a find prefix imply that they return a subset of all objects.
They always return a \Traversable or an array of objects.

So a generic version would be findBy(array $attributes). A more specific one would
be findByName($name) or findByEmail($email).

This is by the way the opposite behaviour of the most laravel classes.

I prefer to return always deferred loading \Traversable objects. This has the
benefit of producing no overhead before really iterating and moving pagination
into the returned object.

It has the drawback that the check for an empty result is not as nice. But this
is a missing feature in php that you cannot overload casting to
bool. (if ($result = $provider->find()) does not work when working with objects)

The best way in most cases to my opinion is to defer the loading into the returned
iterator. You can also add pagination to this traversable object.

search
-------------------------------------
Search creates a Search object. So it is basically a factory for fully
customizable searches. See Ems\Contracts\Model\Search. So you could add a
method search(array $criteria=[]) and return a Search object to add further
->filter() stuff.
It also supports pagination and deferred loading.

get vs. find in Detail
-------------------------------------

If you have a unique constraint and want to retrieve one object use *get*

If you want to retrieve multiple objects use *find*

If you have no unique constraint and want to retrieve one (the first??) you can
use findByName($name)->first() if you return a Ems\Contracts\Model\Result.

I see no reason to have a unique constraint and return multiple objects by
querying it.

load
-------------------------------------
Load is used if you have to retrieve the data from an external source. From a
file, a db, memory,...
Here you can see a kind of implementation specific naming so I would avoid this
term in interfaces unless you write a specific file or db/orm library.

fetch
-------------------------------------
Fetching implies that someone has to move somewhere and get some data and then
bring it back (dogs fetch a stick). So this is even more specific to an
implementation then load. I would avoid that too unless you are writing a
networking/remote specific library.

retrieve
-------------------------------------
see fetch

obtain
-------------------------------------
I would not use it. In most cases acquire is more common to use. And in others
it somehow seems to clash with get.

Conclusion
-------------------------------------
So in EMS you would take the Provider Interface to return one Object by *the one
unique* constraint of that kind of object.

If you need to retrieve it by another unique constraint use getBy*().

If you need to retrieve many or a subset use findBy*($criteria).

If you want to have extensive search capabilities use search(array $attributes=[])
and return a Search Object.

If you don't want to use Search objects and have some filtering support use
filter(array $attributes).

