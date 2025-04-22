# Laravel Fsm

## Installation

```shell
composer require rapid/fsm
```

## Context
Contexts are the building blocks of FSM. Each context is a collection of states that it manages.

```php
class MyContext extends Context
{
    protected static string $model = MyModel::class;

    protected static array $states = [
        'first' => FirstState::class,
        'second' => SecondState::class,
    ];
}
```


### Context model
Each context needs to be connected to a model and store information about the FSM in itself.

The model must use `InteractsWithContext`:

```php
class MyModel extends Model
{
    use InteractsWithContext;
}
```

And have the following columns:

```php
$table->string('current_state')->nullable();
$table->nullableMorphs('parent');
```

And also implement the following method:

```php
protected function contextClass(): string
{
    return MyContext::class;
}
```

Then, if you have a record, you can use the following helpers:

```php
// Context object
$context = $record->context;

// Current state value
$state = $record->state;

// Current deep state value
$state = $record->deepState;
```

Or you can use the following scopes in your queries:

```php
MyModel::query()
    // Add condition where the state is instance of these classes (or interfaces)
    ->whereState(FooState::class)
    ->whereStateNot(BarState::class)
    ->whereStateIn([FooState::class, MyContract::class])
    ->whereStateNotIn([FooState::class, MyContract::class])
    
    // Add conditions where the state is these classes
    ->whereStateIs(FooState::class)
    ->whereStateIsNot(BarState::class)
    ->whereStateIsIn([FooState::class, BarState::class])
    ->whereStateIsNotIn([FooState::class, BarState::class])
;
```


### Context states
You need to declare the list of states in the $states variable. It is absolutely necessary
to declare all states, but one thing you need to know is that states are not ordered.

The key you define is stored in the current_state column of the model,
instead of the state class name. Defining a key is optional,
but it helps with database readability.

```php
protected static array $states = [
    Foo::class,             // save as "App\States\Foo"
    'bar' => Bar::class,    // save as "bar"
];
```

If you need to dynamically define your states,
just define the states method instead of defining the $states variable.

```php
public static function states(): array
{
    return config('custom.my_fsm.states');
}
```

### Transitions
If you want the current state to change, you can do so simply with transitionTo.

```php
$state = $context->transitionTo(SecondState::class);
```


## State
Each record can be in a state at any given time.
Each state must specify the characteristics of that state.

```php
class FirstState extends State
{
}
```


## Nested context
You can also create nested FSMs.

In fact, every Context is a State. So, you can use a context instead of your state,
and then introduce that context in the states list of the parent context.

```php
class MyFsmContext extends Context
{
    protected static string $model = MyFsmModel::class;

    protected static array $states = [
        'foo' => FooState::class,
        'bar' => BarContext::class,
        'baz' => BazState::class,
    ];
}

class FooState extends State {}
class BazState extends State {}

class BarContext extends Context
{
    protected static string $model = BarFsmModel::class;

    protected static array $states = [
        'foo' => FooState::class,
    ];
}
```

In the child context, you can customize the record creation (note that the record must exist)

1. If you want it to be created once and loaded later:

```php
public function onLoad(): void
{
    $this->loadRecord() or $this->createRecord([]);
}
```

You can also customize the logic that should be performed when entering this context.
For example, upon entering this context, the context itself should go to a specific state:

```php
public function onEnter(): void
{
    $this->transitionTo(FooState::class);
}
```


## Start a fsm

Starting an FSM is as simple as this:

```php
$myRecord = MyFsmRecord::create([
    'user_id' => auth()->id(),
]);

$myRecord->context->transitionTo(FirstState::class);
```


## Configuration
Inherit from the `ContextConfiguration` interface or the `DefaultContextConfiguration`
class to customize your context settings.

```php
class MyConfiguration extends DefaultContextConfiguration
{
}
```

Then introduce it into your context:

```php
class MyContext extends Context
{
    protected static string $configurationClass = MyConfiguration::class;
}
```


## Log
Inherit from the Logger interface or the EmptyLogger class to customize your logging.

```php
class MyLogger extends EmptyLogger
{
}
```

For example, if you want to store a record in a table as a log when a transition occurs:

```php
class MyLogger extends EmptyLogger
{
    public function transition(PendingLog $log): void
    {
        FsmTransitionLog::create([
            'from' => $log->fromState
        ]);
    }
}
```
