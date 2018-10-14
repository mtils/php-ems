Provider Interface and method naming
======================================

A provider is basically nothing more than a "get a value for key" class. This
makes it somewhat equal to the offsetGet() method of Storage.

A Provider is the minimal interface for classes that just have to deliver
and not to persist anything. In most use cases you only need a provider.

Regarding [this great Answer](https://stackoverflow.com/questions/2141818/method-names-for-getting-data)
the following naming conventions are used in ems:

get
-------------------------------------
All methods with a get prefix imply at first that the criteria is totally clear
and this method fill return only one result.
So the most basic implementation is get($databaseId) but also getting one result
by passing a criteria is possible: getByLogin() / getByPath() ...

But it has to be clear that the passed argument has to be unique for all objects.

find
-------------------------------------
All methods with a find prefix imply that they return a subset of all objects.
They always return a \Traversable or an array of objects.

So a generic version would be find(array $attributes). A more specific one would
be findByName($name) or findByEmail($email).

This is by the way the opposite behaviour of the most laravel classes.

I prefer to return always deferred loading \Traversable objects. This has the
benefit of producing no overhead before really iterating and some nice methods.

It has the drawback that the check for an empty result is not as nice. But this
is to my opinion a missing feature in php that you cannot overload a casting to
bool. (if ($result = $provider->find()) does not work) 

search
-------------------------------------
Search creates a Search object. So it is basically a factory for fully
customizable searches. See Ems\Contracts\Model\Search. So you could add a
method search(array $attributes=[]) and return a Search object to add further
->filter() stuff.


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

 
 