# Silverstripe SEO Module

## Maintainer Contact

* Bart van Irsel (Nickname: hubertusanton)
* [Dertig Media](http://www.30.nl)


## Requirements

* SilverStripe 3.0

## Documentation

This modules helps the administrator of the Silverstripe website in getting good results in search engines.
A rating of the SEO of the current page helps the website editor creating good content around a subject
of the page which can be defined using a google suggest field.

The fields for meta data in pages will be moved to a SEO part by this module.
This is done for giving a realtime preview on the google search result of the page. 

## Screenshots

![ScreenShot](https://raw.github.com/hubertusanton/silverstripe-seo/master/images/screen2.png)
![ScreenShot](https://raw.github.com/hubertusanton/silverstripe-seo/master/images/screen3.png)

## Installation
Place the module dir in your website root and run /dev/build?flush=all

## TODO's for next versions

* Check img tags for title and alt tags
* Option to set social networking title and images for sharing of page on facebook and google plus
* Create a google webmaster code config 
* Only check for outgoing links in content ommit links within site
* Usage of half stars?
* Translations to other languages
* Check for page subject usage in other pages 
* Check how many times the page subject has been used and give feedback to user
* (Re)Calculate SEO Score in realtime with javascript without need to save first
* Put html in cms defined in methods in template files
* Silverstripe 3.1 compatibility? Some Meta fields are gone in 3.1?!

