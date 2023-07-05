<h1 align="center">
  <img src="https://raw.githubusercontent.com/Ethan3600/randomStuff/master/Images/logo1.png" alt="Cron Job Manager" width="400">
  <br>
  EthanYehuda_CronJobManager
  <br>
</h1>

<h4 align="center">A Cron Job Management and Scheduling tool for Magento 2</h4>

<p align="center"><i>Control Your Cron</i></p>

<p align="center">
  <a href="https://packagist.org/packages/ethanyehuda/magento2-cronjobmanager">
    <img src="https://poser.pugx.org/ethanyehuda/magento2-cronjobmanager/v/stable"
         alt="Latest Stable Version">
  </a>
  <a href="https://packagist.org/packages/ethanyehuda/magento2-cronjobmanager/stats">
    <img src="https://poser.pugx.org/ethanyehuda/magento2-cronjobmanager/downloads"
         alt="Total Downloads">
  </a>
  <a href='https://coveralls.io/github/Ethan3600/magento2-CronjobManager'>
    <img src='https://coveralls.io/repos/github/Ethan3600/magento2-CronjobManager/badge.svg' alt='Coverage Status' />
  </a>
  <br>
  <a href="https://github.com/Ethan3600/magento2-CronjobManager/actions/workflows/coding-standard.yml">
    <img src="https://github.com/Ethan3600/magento2-CronjobManager/actions/workflows/coding-standard.yml/badge.svg" alt="ExtDN M2 Coding Standard">
  </a>
  <a href="https://github.com/Ethan3600/magento2-CronjobManager/actions/workflows/integration.yml">
    <img src="https://github.com/Ethan3600/magento2-CronjobManager/actions/workflows/integration.yml/badge.svg" alt="ExtDN M2 Integration Tests">
  </a>
  <a href="https://github.com/Ethan3600/magento2-CronjobManager/actions/workflows/unit.yml">
    <img src="https://github.com/Ethan3600/magento2-CronjobManager/actions/workflows/unit.yml/badge.svg" alt="ExtDN M2 Unit Tests">
  </a>
</p>

## Installation

In your Magento2 root directory, you may install this package via composer:

`composer require ethanyehuda/magento2-cronjobmanager`

`php bin/magento setup:upgrade`


## Support

Magento version | CronjobManager
--- | ---
Magento 2.4.6 | :white_check_mark: `^1.15 \|\| ^2.0`
Magento 2.4.5 | :white_check_mark: `^1.13.3 \|\| ^2.0`
Magento 2.4.4 | :white_check_mark: `^1.13.3 \|\| ^2.0`
Magento 2.4.x | :white_check_mark: `^1.0`
Magento 2.3.x | :white_check_mark: `^1.0`
Magento 2.2.x | :white_check_mark: `^1.0`
Magento 2.1.x | :x: Not supported
Magento 2.0.x | :x: Not supported

## Features

### Full Control Over All Scheduled Cron Jobs

Take command of all processes running on your Magento 2 instance. You will be able to manage all scheduled cron jobs, which means you have complete control over what tasks fire behind the scenes. An administrator will have the ability of scheduling, removing, editing, analyzing, and running any, and all cron jobs in the cron_schedule table.

![](https://github.com/Ethan3600/magento2-CronjobManager/assets/334786/c8f227a3-eb68-4837-90fb-bb0f387b7b2e)

### Informative Timeline

With the **Timeline** feature, you can see all scheduled tasks registered by Magento's scheduler queue, and quickly analyize important details pertaining to all your tasks. The timeline feature comes with dynamic scaling, live reloading, and tooltips to help you better interface with the scheduler.

<img src="https://user-images.githubusercontent.com/6549623/39410783-98b957fa-4bcb-11e8-9290-71c6597ef828.png"/>


### Control Over Task Configurations

The configuration panel boasts a list of features including:

* Cron expression editing

   Grants access to changing the frequency of any cron job in Magento

   This also allows you to **disable** cron jobs by removing the expression

* System default configuration restore

   Revert back to the system's default configuration

* Schedule Now

   Gives the ability to schedule any task immediately and in the background.
   Scheduling a task from the configuration panel will allow the system to call it asynchronously.

### Command Line Tools

Use the command line tools to run any cron job and view all tasks in the system:

For example: `php bin/magento cronmanager:showjobs`

<img src="https://user-images.githubusercontent.com/6549623/39410837-41f1b060-4bcc-11e8-8b98-7d7253662d5c.png"/>

### Email notifications

You can configure email addresses to be notified if a job has an error.
These settings can be found in Stores -> Settings -> Configuration -> Advanced -> System -> Cron Job Manager.

![email-configuration](https://user-images.githubusercontent.com/367320/60760081-a3970000-a02f-11e9-9615-3eb6c3bd9adb.png)

### And Much More...

The Cron Job Manager is an arsenal of tools that administrators can use to manipulate Magento's scheduler features. It's perfect for debugging obscure issues with custom or native processes (cron jobs) that run on Magento's scheduler queue. There are many use cases where administrators need to keep track of tasks and force them to behave in a specific way. The Cron Job Manager can do it all!

<img src="https://user-images.githubusercontent.com/6549623/39410850-78ca374c-4bcc-11e8-9405-88917a72b5be.png"/>

## Issue Tracking / Upcoming Features

For issues, please use the [issue tracker](https://github.com/Ethan3600/magento2-CronjobManager/issues).

Issues keep this project alive and strong, so let us know if you find anything!

We're planning on pumping out a ton of new features, which you can follow on our [project page](https://github.com/Ethan3600/magento2-CronjobManager/projects/1).

### Development / Contribution

If you want to contribute please follow the below instructions:

1. Create an issue and describe your idea
2. [Fork this repository](https://github.com/Ethan3600/magento2-CronjobManager/fork)
3. Create your feature branch (`git checkout -b my-new-feature`)
    * **NOTE**: Always branch off the `*-develop` branch (ex. 1.x-develop)
4. Commit your changes
5. Publish the branch (`git push origin my-new-feature`)
6. Submit a new Pull Request for review

## Maintainers

Current maintainers:

* [Ethan Yehuda](https://github.com/ethan3600)

See also our [contributers](https://github.com/Ethan3600/magento2-CronjobManager/graphs/contributors)


## License

[The Open Software License 3.0 (OSL-3.0)](https://opensource.org/licenses/OSL-3.0)
