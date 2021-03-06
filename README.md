# Better Api Composer Plugin
To provide extended functionality to the TYPO3 core we need to listen for loaded classes. 
However, TYPO3 uses the ClassAliasLoader which registers itself (you can configure this now, but the default is still TRUE) as first possible autoloader (spl_autoload_register -> FLAG prepend is set to true).

The resulting problem is, that there is literary no way of adding an additional class loader to the stack. 

Why do I need a class loader? Well, I have to bootstrap the extension as soon as the environment builder prepared the "Environment" class
to create the "core modding" class overrides. But there is no hook or signal or anything I could use to call said bootstrap other
than watching for required classes and trigger the better api bootstrap as soon as the "\TYPO3\CMS\Core\Core\Bootstrap" class is loaded. 

This plugin is a temporary solution to the problem. As soon as I see a better one I will gladly drop this composer plugin again.

## Usage in a development environment
If you want to install a package, that somehow depends on this plugin without running in a real TYPO3 project (aka. Dev or Test Scenario),
make sure you set the following flag in your composer.json to avoid crashes!

```json
{
  "extra": {
    "T3BA": {
      "isDev": true  
    }
  }
}
```

## Postcardware
You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: LABOR.digital - Fischtorplatz 21 - 55116 Mainz, Germany

We publish all received postcards on our [company website](https://labor.digital). 
