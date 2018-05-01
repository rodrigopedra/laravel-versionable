# Versionable
## Easy to use Model versioning for Laravel 5.6

> Based on https://github.com/mpociot/versionable

Keep track of all your model changes and revert to previous versions of it.

```php
// Restore to the previous change
$content->previousVersion()->revert();

// Get model from a version
$oldModel = Version::find(100)->getModel();
```



## License

Laravel Versionable is free software distributed under the terms of the [MIT license](https://opensource.org/licenses/MIT).
