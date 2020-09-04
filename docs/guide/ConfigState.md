# Config State
The config state is the result of the configuration loader.
It holds (in general) all collected information provided by the config classes.
It provides a simple interface to retrieve and even update its contents at runtime.

## General API

### has()
Returns true if a given key exists, false if not.
Note: The method is namespace sensitive!

::: details Arguments
- $key  A key or path to get the value for
:::

### get()
Returns the stored value for a given key, or returns the $fallback
if the key was not found. Note: The method is namespace sensitive!

::: details Arguments
- $key       Either a simple key or a colon separated path to find the value at
- $fallback  Returned if the $key was not found in the state
:::

### getAll()
Returns the whole state content as an array

### set()
Sets a value in the config state
Note: The method is namespace sensitive!

::: details Arguments
- $key    Either a simple key or a colon separated path to set the value at
- $value  The value to set for the given key.
        NOTE: When the state is cached all values MUST be JSON serializable!
:::

## Path Access
All actions performed on the collected state are executed using the [Neunerlei/Arrays](https://github.com/Neunerlei/arrays) package.
This means all $keys can also be colon separated paths ([see the definition](https://arrays.neunerlei.eu/guide/PathAccess.html#path-definition)) for deep lookups.

## Namespacing
If you perform multiple actions in the same sub path it may come in handy to define a certain namespace.
```php
<?php
use Neunerlei\Configuration\State\ConfigState;
$state = new ConfigState([]);

// Instead of this
$state->set('foo.bar.baz', 1);
$state->set('foo.bar.bar', 2);
$state->set('foo.bar.roo', 3);

// You can do this, which will lead to the same result
$state->useNamespace('foo.bar', function($state){
    $state->set('baz', 1);
    $state->set('bar', 2);
    $state->set('roo', 3);
});

// You can even nest this method like so
$state->useNamespace('foo.bar', function($state){
    $state->set('baz', 1);

    // "bar" will be added on the root level, because the namespace is set to NULL
    $state->useNamespace(null, function($state){
        $state->set('bar', 123);
    });

    // Note, that namespaces do not stack. So this leads to "bar.baz.foo" and not to "foo.bar.bar.baz.foo"
    $state->useNamespace('bar.baz', function($state){
        $state->set('foo', 2);
    });

    // This will now be back to "foo.bar.foo"
    $state->set('foo', 1);
});
```

## Watchers
The config sate object is watchable by your code. This means if a config property is changed on runtime
you can register a callback to do something with the changed value.

Adds a new watcher for a storage key. Watchers will be executed every time
their respective key or one of their children gets updated using the set() methods.

This means you can get notified if a configuration state changes.

::: tip Example
If you register a watcher on the key: "foo.bar"
and set a value to $state->set('foo', ['test' => 123]);
your watcher will NOT be triggered, because "foo.test" is not being watched.

However, if you set another value to $state->set('foo', ['bar' => 123]);
your watcher will be triggered, because "foo.bar" got updated.

It will also work if you set $state->set('foo.bar', 123) directly.

The watcher will also get notified if you set one of its children.
This means if you set something like $state->set('foo.bar.baz', 123), the watcher will trigger,
because it listens to deep changes.
:::

::: warning
Only simple paths are supported as watcher keys, meaning stuff like "foo.bar[foo,bar]" or "foo.*.bar" will NOT work!
:::

```php
<?php
use Neunerlei\Configuration\State\ConfigState;

$state = new ConfigState([]);
$state->addWatcher('foo', function ($v) {
    // $v will be 'asdf'
    echo $v;
});
$state->set('test', 'asdf');
```

## Object property syncing
In some cases you have to access specific config properties hundreds or thousands of times while the script is executed.
(Think of options that may affect how translation labels are handled or repetitive tasks like table rendering.)
This may lead to a lot of overhead if you always use $configState->get('your.key').

You can of course store the config property before you start the repetitive task, but that means if the configuration
was changed during runtime you might not get the latest state, leading to a de-sync between configuration
and your implementation.

To prevent this you can use the "LocallyCachedStatePropertyTrait" that allows you to keep a local property in sync
with a specific configuration state.

As an example: We have our translator that gets the language from the config state
```php
<?php
use Neunerlei\Configuration\State\ConfigState;
use Neunerlei\Configuration\State\LocallyCachedStatePropertyTrait;

class Translator {
    use LocallyCachedStatePropertyTrait;

    /**
     * The two char language code to translate
     * @var string
     */
    protected $languageKey;

    /**
     * The list of translations
     * @var array
     */
    protected $translations = ['en' => ['...'], 'de' => ['...']];

    public function __construct(ConfigState $state){
        // This will now keep the $this->languageKey property in sync with translation.languageKey
        // In your config state object. So even if "translation.languageKey" was changed your translator
        // will use the the correct language key, without the overhead of firing a query every time
        // a label gets translated
        $this->registerCachedProperty('languageKey', 'translation.languageKey', $state);
    }

    public function translate(string $label): string {
        return $this->translations[$this->languageKey][$label];
    }
}
```
