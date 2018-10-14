Repository Interface and Type Handling
======================================

In opposite to a storage a repository handles objects of a unique class for you.
You can do the typical CRUD stuff by a repository.

To ensure a unique interface there is a central repository interface.
Unfortunately you cannot typehint different classes in extended interfaces.

Therefore only the most basic EMS interface is used in that repository:
Identifiable. If you ask for an object or you want to find it again you usually
need an identifier. So at a minimum the object has to have an id.

The options how to write a central repository in this case were just two:

* No method type hints in no class
* A central basic type hint

Now the implementation (and argumentation) is like follows:

a) You need a way to store some entity in YOUR application
----------------------------------------------------------
The first situation is that you usually need some kind of entity. As an example
we will take a subscription.
Just imagine you build an application that allows users to subscribe to channels
or products or services.

So you are writing *your* interface for a subscription and some:

Remember SOLID here, only program to interfaces.

```php
<?php

namespace Membership\Contracts;

interface Subscribable
{
    public function getId();
    public function getMinDate();
    public function getMaxDate();
    public function getDays();
}

interface Subscription
{
    public function getId();

    /**
      * @return User (interface)
     */
    public function getSubscriber();
    
    /** 
     * @return Subscribable (interface)
     */
    public function getSubscribed();
    
}

interface SubscriptionRepository
{
    /**
     * @param int $id
     * 
     * @return Subscription
     */
    public function get($id);

    /**
     * @param User $user
     *
     * @return Subscription[]
     */
    public function allOf(User $user);

    public function subscribe(User $user, Subscribable $subscription);
    
    public function unsubscribe(User $user, Subscribable $subscription);
}

```

b) Then in all your classes you just refer to YOUR interfaces (not ems)
-----------------------------------------------------------------------

Just inject your interfaces in your using classes.


```php
<?php

namespace Membership\YourRoutingFramework;

class SubscriptionController
{
    /**
     * @var SubscriptionRepository (your interface!!)
     **/
    protected $subscriptions;

    /**
     * @var UserRepository (your interface!!) 
     */
    protected $users;

    public function __construct(SubscriptionRepository $subscriptions, UserRepository $users)
    {
        $this->subscriptions = $subscriptions;
        $this->users = $users;
    }

    public function indexOfUser($userId)
    {
        $user = $this->users->getById($userId);
        $subscriptions = $this->subscriptions->allOf($user);
        
        return $this->view->render(
            'subscriptions.index.phtml',
            ['user' => $user, 'subscriptions' => $subscriptions]
        );
        
    }
}


```
This ensures that you only use your interface methods in your controllers. If
you have a decent IDE it will show you only the methods of your interfaces.
The autocompletion of $user or $subscriptions and $this->subscriptions and
$this->users will show only methods of your interfaces. So you are in this
controller totally independent from the implementation of SubscriptionRepository
or Subscription or something else.

b) Then you have to implement the interfaces
-----------------------------------------------------------------------
Now implement the interfaces in the framework of your choice.
In this sample I would use Ems\Model but this could also be Eloquent, Doctrine
or Propel.
Note that this is no complete implementation its just for documentation purpose.

```php
<?php

namespace Membership\Ems;


class OrmVideoChannel extends OrmObject implements Subscribable
{
    public function getMinDate()
    {
        return DateTime::createFromFormat('format', $this->min_date);
    }

    public function getMaxDate()
    {
        return DateTime::createFromFormat('format', $this->max_date);
    }

    public function getDays()
    {
        return $this->days;
    }
}

class OrmSubscription extends OrmObject implements Subscription
{
    /**
      * @return User (interface)
     */
    public function getSubscriber()
    {
        return $this->getRelation('subscriber');
    }
        
    /** 
     * @return Subscribable (interface)
     */
    public function getSubscribed()
    {
        return $this->getRelation('subscribed');
    }
}

class OrmSubscriptionRepository implements SubscriptionRepository
{

    /**
     * @var \Ems\Contracts\Core\Repository 
     */        
    protected $emsRepository;

    /**
     * @param int $id
     * 
     * @return OrmSubscription
     */
    public function get($id)
    {
        return $this->emsRepository->get($id);
    }

    /**
     * @param User $user
     *
     * @return OrmSubscription[]
     */
    public function allOf(User $user)
    {
        return $this->emsRepository->all(['user_id' => $user->getId()]);
    }

    public function subscribe(User $user, Subscribable $subscription)
    {
        $this->checkIsOrmObject($subscription);

        /** @var OrmSubscription $subscription */
        // Do the insert stuff
    }
    
    public function unsubscribe(User $user, Subscribable $subscription)
    {
        // Do the delete stuff
    }

    protected function checkIsOrmObject($object)
    {
        if (!$object instanceof OrmObject) {
            throw new \RuntimeException();
        }
    }
}

```

The important part here is that inside the repository you work with implementation,
in this case ems. Here you are allowed to use the ems *interface* stuff.
In the method subscribe() u see that you have to explicitly tell your IDE
what this object really is. Otherwise it has only the interface. And the
interface wont allow you to set values.

But this is the whole purpose of the repository pattern: hide the implementation
detail from your application behind repositories (repository interfaces).

So by the reason that you have to test inside your repository always that it is
in this case an OrmSubscription that was passed and not an
DoctrineSubscription or EloquentSubscription you have to check that inside
manually. (And for this reason I saw no problem in the Identifiable type hint in
Ems\Contracts\Core\Repository). 

You should also write your implementation specific type into your @return statements
like in the example above.

*Important*: This is because every class that uses the implemented *class* explicitly is already bound
to that implementation and knows what its returning!

So avoid classes directly, use only interfaces as strict as you can.

An also use interfaces for your value objects. The worst thing you can do is
returning something like Eloquent Objects and type hint that and use the 1 million
public methods of Eloquent\Model. Then you will be married to Eloquent forever.

Why this abstraction? I will never leave 'my favourite framework'
-----------------------------------------------------------------------
This is so complicated and so much code...I want to directly mysql queries and
Eloquent where()->where() stuff! I will never change my dbms or framework...

This is a thing I here very often: It is not the main reason that there is any
plan in changing the framework or dbms.

Frameworks do change. Example: I directly used the laravel container in a lot of
places (mainly ServiceProviders) and made a lot of $app->make($class, []); calls.
And then they removed the support for indexed parameters in make.
I really wish I had ONE place to change that and not every place were I used it
directly. I was not moving away from laravel.

Backends do change. Example: In the last years a lot of that micro service stuff was
getting really popular. So database driven stuff was often moved into the REST
webservices. Every direct usage of an implementation specific object
(like Eloquent) made it nearly impossible to port it from Eloquent to a REST
backend.
This just destroys the whole purpose of using interfaces in this case.

So really, if you want to write SOLID and you want to use interfaces you have to
do it the way it really works.

You can also decide that you don't program to interfaces because it will never be
implemented differently. This is also valid and totally okay. The code will not
be as durable but cheaper.
But then you can really avoid to whole interface stuff and save your time.

But if you plan to build a durable professional application program SOLID.