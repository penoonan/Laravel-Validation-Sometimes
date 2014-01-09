#Decoupled Validation With Laravel's `sometimes()` method

I learned a ton reading Chris Fidao's book "Implementing Laravel", and I followed his pattern for form validation very closely in building my first significant Laravel 4 application. Actually, it's the first significant application I've built from the ground up in any framework *whatsoever*, so it feels like my baby and I'm proud of every step it takes. 

Of course, sooner or later it was bound to happen that I'd run into a problem that wasn't covered by the thing I copied out of a book. And so it came to pass. The problem was, I needed to use the `sometimes()` method of Laravel's validation class, and I was finding it tough to wrap my head around how to make it happen.

##What does `sometimes()` do?

If you don't know, it's not tough to grasp. [Read this quick](http://laravel.com/docs/validation#conditionally-adding-rules) and come back when you're done.

##Getting Started

First I should explain the pattern as originally sketched out in the book (without, I hope, giving away too much of a copyrighted work that [you should definitely buy yourself](www.link.com) if any of this sounds unfamiliar to you. Some class names have been changed to protect the identities of those involved. 

It starts with the basic pattern of interface -> abstract class -> concrete implementation: ValidatorInterface, an AbstractValidator which implements it, and a ConcreteValidator that extends the abstract class. The idea is simply to provide decoupled, dependency-injected access to Laravel's built-in Validation class. As a result, the methods defined in ValidatorInterface are just `with()`, `passes()` and `errors()` - the most basic methods of that class and the ones whose functionality should be shared by *other* validation classes in the event you find yourself needing to swap in something new. 

AbstractValidator implements all the methods defined in ValidatorInterface, and any concrete implementations are able to start from that base and add whatever their specific implementation requires. This decoupled approach is different from what you'll see in Laravel's docs on Validation. There, you'll see how to use the Validator facade to spin up an instant validator, roughly like so: `$validator = Validator::make($data, $rules, $messages);` It's a great approach, but if you found yourself needing to swap in a new validator, you'd be out of luck *unless* it happened to share an interface with Laravel's. Not likely.

In Fidao's example, ConcreteValidator's `$rules` and `$messages` are simply defined as default class property values. The class uses constructor injection to pass an instance of Illuminate\Validation\Factory (which it acquires via a Laravel service provider) to its parent AbstractValidator. The AbstractValidator doesn't actually create a concrete Validator instance until ConcreteValidator runs its `passes()` method. It would be hard to change that without diminishing the elegance of the AbstractValidator API --- and therein lay the problem.

##When is `sometimes()`?

Unlike defining rules and messages or even extending the Validator class with custom rules of your own, the `sometimes()` method cannot be called on the Validator Factory - it can only be called on a concrete instance of the class. But since AbstractValidator only called `make()` *inside the `passes()`* method, there was no simply no room for ConcreteValidator to insert a call to `sometimes()`. Let's take a peek:

    public function passes() 
    {
        $validator = $this->validator->make(
            $this->data,
            $this->rules,
            $this->messages
        );
    
        if( $validator->fails() ) {
            $this->errors = $validator->messages();
            return false; 
        }
    
        return true;
    }

The validator is made (that is, the `make()` method is invoked on the injected factory instance), and it instantly calls `passes()` --- well, in this case, it calls `fails()`.  The entire body of the `fails()` function is `return !passes();`. So it's the same diff.

##A closer look at `sometimes()`

Let's take a closer look at the usage of that `sometimes()` function, shall we:

    $v->sometimes(array('reason', 'cost'), 'required', function($input)
    {
        return $input->games >= 100;
    });

We can see that it accepts three parameters: the first indicates the field(s) to be validated against, the second contains the name of the validation rule to be applied, and the third is a closure or callback containing the logic that defines when the specified rule is to be applied. Note that the callback receives a single parameter `$input` - that's *all* the user input you've passed into the validator via the `$data`. 

##An Implementation

I decided to create an implementation that would follow Fidao's example as closely as possible, so that `sometimes()` rules could be implemented right alongside normal rules and messages. The first step was to initialize a variable named `$sometimes` inside the AbstractValidator class. It would need to be able to contain multiple `sometimes()` rules, so an array seemed like a good choice:

    /**
     * Conditional rules that are only used "sometimes",
     * but which must be invoked *after* the concrete
     * validator has been instantiated
     * @var array
     */
    protected $sometimes = array()];

Each `$sometimes` rule needs to contain all the parameters that the `sometimes()` function requires, so any concrete implementation would need to look something like this:

    protected $sometimes = array(
        array(
            'field' => 'last_name',
            'rule' => 'required',
            'callback' => 'checkLastNameIsRequired'
        ),
    );

But there's still a little magic left to be done! ConcreteValidator needs to implement that callback - we can do it just like this:

    public function exampleCallback($input)
    {
        return true === true ? true : false;  //Why not?
    }

Finally, we need to tell the AbstractValidator how to handle any `sometimes()` rules that may be present in one of its concrete implementations. I chose to accomplish that by modifying the `passes()` method thusly:

    public function passes() 
    {
        $validator = $this->validator->make(
            $this->data,
            $this->rules,
            $this->messages
        );

        //This loop is all I added to the original method
        foreach ($this->sometimes as $sometime) {
            $validator->sometimes(
                $sometime['field'],
                $sometime['rule'],
                function($input) use ($sometime)
                {
                    return $this->$sometime['callback']($input);
                }
            );
        }
    
        if( $validator->fails() ) {
            $this->errors = $validator->messages();
            return false; 
        }
    
        return true;
    }

And that did it! So there you have it, peruse the included files to see it all fleshed out. There are, of course, many ways to peel a tomato, so if you've got suggested improvements, let me know. Thanks for reading!
