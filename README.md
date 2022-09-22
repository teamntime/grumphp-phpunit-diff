# GrumPHP PHPUnit Diff Task

Whoa, another PHPUnit task for GrumPHP? Well kind of.

We at Team n Time run GrumPHP both on our development machines and on our CI servers. All of our PHPUnit test suites
run on CI to ensure we didn't create a regression or broke something. You could opt to run your testsuites also locally
but that might not always be practical. If you get over a certain amount of assertions, this could become cumbersome.

For us, the goal is to balance not wasting developer hours vs. not wasting CI runs. It's in this balancing act that
we've dreamt up having tooling that runs just tests on the difference. But what does that mean? GrumPHP handily provides
you with the file names of the changed files, but there is no standard that makes locating test files of PHP classes 
easy.

That's where this task comes in. It allows you to define the structure of your project by creating a simple PHP function
that gives you the PHP class filepath and allows you to define where it's test is located. We do this with simple
str_replace, but you might opt for any other strategy.

## Install and using

Install by running composer:

```
$ composer require --dev TeamnTime/grumphp-phpunit-diff
```

Create a PHP file with your location function that have the following structure:

```
<?php

namespace TeamnTime\GrumphpPhpunitDiff;

function transformClassToTest(string $changedClassName): ?string
{
    return ''; // Implement your logic
}

```

Update your grumphp.yml file:

```
grumphp:

#...

    phpunit_diff:
        config_file: ./phpunit.xml.dist # required: Path to the existing PHPUnit file, relative to the project dir
        always_execute: false # optional
        always_delete_generated_configs: false # optional: When the task fails, does it leave the generated PHPUnit files or not
        order: null # optional
        
    extensions:
        - TeamnTime\GrumPHP\PHPUnitDiff\ExtensionLoader
#...

```

## How does it work?

There's two problems that this package solves:

1. How to locate a test for a changed file/class: We've solved this by introducing a function that allows any project to customise how the relation between test <-> class is set up
2. PHPUnit does not allow you to pass multiple files as arguments, only directories: We've solved this by duplicating the existing PHPUnit config and adding a Testsuite with the changed files.
