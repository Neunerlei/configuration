# Getting started
I tried to make the library as versatile as possible while keeping it as simple as possible.
You can always take a look into the "Example" directory in the repository to get an overview over all options in action.

## Root locations
This package is designed to work either in combination or as a standalone plugin system.
Sadly, this means we have to crunch a bit of boring theory before we can start with setting everything up.
In your project you will probably have a directory called "plugins" (or something similar), where
all your plugins reside (presumably each in their own directory). In that case
your directory structure might look like this:

```
- src
  - Classes
    - ... [project classes]
  - Config
    - ... [project config]
  - Plugins
    - Plugin1
        - Config
            - ... [config classes]
        - Handlers
            - ... [handler classes]
        - ... [plugin sources]
    - Plugin2
        - Config
            - ... [config classes]
        - Handlers
            - ... [handler classes]
        - ... [plugin sources]
```

The directory where the plugin sources are stored (/src/Plugins/Plugin1 and /src/Plugins/Plugin2),
are the so called "RootLocations". Those folders should be traversed by the configuration loader
to find the available configuration classes.

::: info
Note, that we did not define a /Config directory but simply use the base plugin directory.
It will become clear why we do this in the "handlers" section.
:::

Another example for a root location is the configuration of the project itself,
this is where you or your user will be able to configure installed plugins or framework options.
To achieve the project configuration we have to register an additional root location at /src.

This means we have currently three root locations to register:

- /src/Plugins/Plugin1
- /src/Plugins/Plugin2
- /src


::: tip
The order is important here, because that means that the config of both plugins will be loaded before the app config is loaded.
:::

So far, so good? Now lets jump into the action.

## Basic setup
First of all include the composer autoloader (if not done by your framework/app already):

```php
<?php
include __DIR__ . '/vendor/autoload.php';
```

Now let's create the configuration loader instance.
The configuration loader is the repository to load the configuration.

It requires two arguments:

- $type: A unique type key for this configuration, so we don't
         create an overlap when different configurations are loaded.
- $environment Something like "dev"/"prod"/"stage" or similar to
               describe your current environment.

```php
<?php
use Neunerlei\Configuration\Loader\Loader;
$loader = new Loader('test', 'dev');
```

Next up we have to tell the loader where its root locations are.
```php
<?php
/** @var \Neunerlei\Configuration\Loader\Loader $loader */

// Register all plugin directories using a glob
$loader->registerRootLocation('/src/Plugins/*');

// Register the project root directory
$loader->registerRootLocation('/src');
```

Now, as a last step, we have to tell the loader, where it can find handler classes.
Following our example, we have to look inside the "Handler" directory of each plugin folder in order to find handlers,
and so it's as simple as that:
```php
<?php
/** @var \Neunerlei\Configuration\Loader\Loader $loader */

// Relative paths will be resolved, relative to all root locations.
$loader->registerHandlerLocation('Handlers');
```

Finally, we load the configuration:
```php
<?php
/** @var \Neunerlei\Configuration\Loader\Loader $loader */
$state = $loader->load();
print_r($state->getAll());
```

::: tip
After your configuration has been loaded from your configuration class the result will be an object of type:
```\Neunerlei\Configuration\State\ConfigState``` which contains the combined information from all plugins and your project
configuration. You can use its get() and set() methods to retrieve or update the data to your liking.
In its core, the state object is a multi-dimensional array which allows you to store any kind of data.
:::

Well, that's that, but now what? The result of the print_r will be an empty array. This isn't helpful, isn't it?
To get a more sensible output, we have to create a handler first, and a configuration class after that.

## Creating your first handler
A handler is the class which tells the configuration loader which type of classes it can process and where
to look for them, inside your root locations. It is also used to pass the configurator through all found
configuration classes and push the gathered information into the ConfigState object, when done.

Before you can begin configuring your code you have to create a handler class, and a matching configurable interface.
Let's assume we want to create a config handler for Plugin1 in our example above.
So we create a new class inside the /src/Plugins/Plugin1/Handlers directory and call it: "ConfigureTestHandler".

```php
<?php
namespace Example\Plugins\Plugin1\Handlers;
class ConfigureTestHandler {}
```

A handler has to either implement the ```Neunerlei\Configuration\Handler\ConfigHandlerInterface``` interface,
or extend the ```Neunerlei\Configuration\Handler\AbstractConfigHandler``` class. I would always use the second option.
So let's extend the handler and add the stubs for the required methods of the interface:

```php
<?php
namespace Example\Plugins\Plugin1\Handlers;

use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class ConfigureTestHandler extends AbstractConfigHandler{
    public function configure(HandlerConfigurator $configurator) : void {}
    public function prepare() : void {}
    public function handle(string $class) : void {}
    public function finish() : void {}
}
```

As you can see, there are four methods that have to be implemented by a handler. Before we look at those
methods in detail, we create our configurator and configurable interface.

::: tip Context
You may use the $this->context property in your handler, to get the state or runtime information,
which will be automatically injected into your instance.
:::

## Configurator
A configurator is optional while writing a handler, but highly recommended. The configurator object is, in general
the speaking API which will be passed through configuration classes and is used to gather the data. The main
reason to create a configurator is, to tell the world what can be configured and how,
because the configuration implementation is, completely agnostic to the type of data you create with it.

This approach allows you to gather information from multiple plugins and process them after all data has been
gathered. It also serves as an auto-complete friendly setter for the ConfigState object, if you don't require
additional processing for your configuration.

A configurator class can be complex, or as simple as the following example:

```php
<?php

namespace Example\Plugins\Plugin1\Handlers;

use Neunerlei\Configuration\State\ConfigState;

class ConfigureTestConfigurator {

    /**
     * A generic config option
     * @var string $myOption
     */
    protected $myOption;

    /**
     * Sets a generic config option
     */
    public function setMyOption(string $myOption): self {
        $this->myOption = $myOption;
        return $this;
    }

    /**
     * This method is optional, we will use it in our handler
     * to extract the collected data of this configurator after all
     * configuration classes have been processed. This pattern allows you to "post-process" the
     * data before it is set to the state.
     *
     * Note: If you don't want or need that extra layer of processing,
     * you can also set the data directly to the state in your setter methods.
     *
     * @see \Neunerlei\Configuration\Util\ConfigContextAwareInterface
     * @see \Neunerlei\Configuration\Util\ConfigContextAwareTrait
     */
    public function finish(ConfigState $state): void
    {
        $state->set('myOption', $this->myOption);
    }
}
```

### Alternative configurator architecture
Alternatively, in this example you could go the easy route of directly setting the state data.
However, in my experience this is not your normal use case, hence I wanted to show you how to manage bigger configurations from the beginning.
For completeness, this would be the alternative in the simple setup above:

```php
<?php

namespace Example\Plugins\Plugin1\Handlers;

use Neunerlei\Configuration\Util\ConfigContextAwareInterface;
use Neunerlei\Configuration\Util\ConfigContextAwareTrait;

class ConfigureTestConfigurator implements ConfigContextAwareInterface {

    use ConfigContextAwareTrait;

    /**
     * Sets a generic config option
     */
    public function setMyOption(string $myOption): self {
        $this->context->getState()->set('myOption', $myOption);
        return $this;
    }
}
```

::: tip
If you implement the ConfigContextAwareInterface for a class, the internal dependency injection handler
will make sure to provide the config context when a new instance is created.
:::

## Configurable interface
To map a certain configuration class to a handler object the handler defines one, or multiple interfaces
it can process. All classes with the registered interfaces will be processed by the handler.
If multiple handlers can process the same interface, the configuration class will be handled by all of them.

::: tip
In order to make configurable interfaces easy to find you should start their name with "Configure..." and then
write WHAT the interface allows you to configure. (e.g ConfigureHttpInterface, ConfigureMiddlewaresInterface,...).
:::

A simple, but common interface looks like this:

```php
<?php
namespace Example\Plugins\Plugin1\Handlers;

interface ConfigureTestInterface {

    /**
     * Configures some part of your application
     */
    public static function configure(ConfigureTestConfigurator $configurator): void;

}
```

As you can see, the interface only defines a single method called "configure()". The method will receive
the configurator instance. Every configuration class has to implement this interface, and therefore
can inherit the "configure()" method, including the auto-completion type hint for your configurator object.

::: tip
It's up to you, and your requirements on how the interface should look like.
Your handler decides what you want to do with the information, that was collected by the configurator,
the configurable interface only tells the user what they can expect from them.
:::

But, before we get ahead of ourselves, let's take a look at the methods in a handler class:

### configure()
The configure() method is used to tell the config loader about your handler. It allows your handler to provide
information about where it finds its contents, on which interfaces it listens,
or the order in which it should be executed. It is called once, when the loader gathers the handler information.

You can use the given $configurator to tell the loader that our handler should look inside the "Config" directory,
relative to the registered root locations in order to find its configuration classes. We also tell the loader,
it should find only configurations which implement the ```Example\Plugins\Plugin1\Handlers\ConfigureTestInterface```
interface for this handler.

```php
<?php/** @noinspection ALL */
namespace Example\Plugins\Plugin1\Handlers;

use Example\Plugins\Plugin1\Handlers\ConfigureTestInterface;
use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class ConfigureTestHandler extends AbstractConfigHandler{
    public function configure(HandlerConfigurator $configurator) : void {
        // Tell the loader to look inside the "Config" directory of each registered root location.
        $configurator->registerLocation('Config');

        // Tell the loader for which interface it should look there.
        $configurator->registerInterface(ConfigureTestInterface::class);
    }
    // ... other methods
}
```

### prepare()
After the loader resolved the list of configuration classes for your handler, the prepare() method is called once.
In this example, we use the method as a hook to create our configurator instance.

```php
<?php/** @noinspection ALL */
namespace Example\Plugins\Plugin1\Handlers;

use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Example\Plugins\Plugin1\Handlers\ConfigureTestConfigurator;

class ConfigureTestHandler extends AbstractConfigHandler{

    protected $configurator;

    public function prepare() : void {
        // Note how we create the configurator instance using the built-in getInstance() method.
        // it will automatically try to create the instance using the (optional) PSR container implementation
        // or create the instance itself if no container was given
        $this->configurator = $this->getInstance(ConfigureTestConfigurator::class);
    }

    // ... other methods
}
```

### handle(string $class)
The handle method will be called once for every configuration class, and will receive the name
of that class as a parameter. Your handler can decide freely what it does with the name of the class.
In our example, where we defined the "configure()" method as static in our configurable interface,
its job is fairly simple: execute the static method and pass it the configurator object.

```php
<?php/** @noinspection ALL */
namespace Example\Plugins\Plugin1\Handlers;

use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Example\Plugins\Plugin1\Handlers\ConfigureTestConfigurator;

class ConfigureTestHandler extends AbstractConfigHandler{

    public function handle(string $class) : void {
         call_user_func([$class, 'configure'], $this->configurator);
    }

    // ... other methods
}
```

### finish()
After all configuration classes have been processed in the handle() method the finish() method is called once.
Here, the handler can apply post-processing, emit events or whatever you need to do.
In our example what we want is to inherit the information from the configurator and store it in the state
object.

```php
<?php/** @noinspection ALL */
namespace Example\Plugins\Plugin1\Handlers;

use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Example\Plugins\Plugin1\Handlers\ConfigureTestConfigurator;

class ConfigureTestHandler extends AbstractConfigHandler{

    public function finish() : void {
        // We retrieve the state object from the context and pass it to the configurator's finish method.
        // The configurator itself will then store the collected information on the state
        $this->configurator->finish($this->context->getState());
    }

    // ... other methods
}
```

## Writing a configuration class
Now, after all that setup, let's create a configuration and see how it works.
If we look back at the directory structure in the "Root locations" section we see,
that there are multiple directories called "Config". Inside our handler class implementation we told the
config finder, that it should look inside the "Config" directory of all root locations in order to resolve
potential target classes.

So, we start by creating a new class at /src/Plugins/Plugin1/Config:
```php
<?php
namespace Example\Plugins\Plugin1\Config;

class PluginTestConfig {}
```

In order to register the class as configuration we have to implement the configuration interface.

::: tip
If you follow the naming suggestion, by beginning your configurable interface with "Configure...",
you will be able to see a list of all configurable interfaces when you start typing: ```...Config implements Configure|```.
:::

```php
<?php
namespace Example\Plugins\Plugin1\Config;

use Example\Plugins\Plugin1\Handlers\ConfigureTestInterface;

class PluginTestConfig implements ConfigureTestInterface{}
```

::: tip
If you are using PHPStorm you can now right-click on the interface, select "Show Context Actions" and "Add method stubs"
to create the method required by the contract.
:::

```php
<?php
namespace Example\Plugins\Plugin1\Config;

use Example\Plugins\Plugin1\Handlers\ConfigureTestInterface;
use Example\Plugins\Plugin1\Handlers\ConfigureTestConfigurator;

class PluginTestConfig implements ConfigureTestInterface{
    /**
     * Configures some part of your application
     */
    public static function configure(ConfigureTestConfigurator $configurator): void {

        // Use the configurator to fill the registered property
        $configurator->setMyOption('plugin option');
    }
}
```

Now, back in our example above, here you should now see an output of ```['myOption' => 'plugin option']```
after you let your code run again.

Pretty neat, but a lot of code to write a single entry in an array. So, why would I ever do it like this?
I'm glad you asked, so with the basic principles out of the way, take a look at some nifty stuff like:

- Inheritance and overrides
- Handler overrides and config extension
- Config modifiers
- Dependency injection
- Caching and runtime handling
- Namespacing
- Events
- Loader extensions
