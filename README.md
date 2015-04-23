# magento-version-checker #

Ensures that a Magento module has the expected Magento version installed.

## How to use on a project
Add the followings to the composer.json of your Magento project.
```
"repositories": {
    "magento-version-checker": {
        "type": "vcs",
        "url": "git@github.com:inviqa/magento-version-checker.git"
    }
}

"require": {
    "inviqa/magento-version-checker": "*"
}
```

It checks all the packages with the following type: "magento-module".
When it matches it compares the current version (1.1x) and edition (Enterprise or Community) of Magento
with the required Magento version of the module.

## How to use with standalone Magento module
Add the followings to the composer.json of your Magento module.
```
"repositories": {
    "magento-version-checker": {
        "type": "vcs",
        "url": "git@github.com:inviqa/magento-version-checker.git"
    }
},
"require": {
    "inviqa/magento-version-checker": "*"
},
```

You need to specify the required magento versions in the extra section. You can specify the required
enterprise and/or community edition as well.
```
"extra": {
    "magento-version-ee": "^1.12.0", // means >= 1.12.0 Enterpise Edition
    "magento-version-ce": "^1.9.0" // means >= 1.9.0 Community Edition
}
```


## Known issues

You may get the error below when running composer install the first time.
```
[ErrorException]
  include(MagentoHackathon\Composer\Magento\Deploystrategy\.php): failed to open stream: No such file or directory
```
Workaround: run `composer install` again.

## Contributing ##

* Fork the repository `git clone git@github.com:inviqa/magento-version-checker.git`
* Create a topic branch `git checkout -b my_branch`
* Push to your branch `git push origin my_branch`
* Create a Pull Request from your branch, include as much documentation as you can in the commit message/pull request,
following these guidelines on writing a good commit message
* That's it!
