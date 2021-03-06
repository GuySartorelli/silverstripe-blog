# Configuring Blog for large user bases

By default the blog module user and author selection form fields include all users in your website as 
candidates for writers, editors and contributors. Additionally, when adding a blog post, again all users are selectable.
This can cause issues with websites that store a large number of users in the database.

In this case you may need to restrict the number of user accounts that are eligible for selection. 
This module has some useful configuration options for this that can be added to your projects config.yml

## Restricting blog managers to a permission setting
Default is to list all users and when one is selected, they are added to a `blog-users` group with `CMS_ACCESS_CMSMain` permissions.
To only include those already having these permissions you can set in your `config.yml`:

```yaml
SilverStripe\Blog\Model\Blog:
  grant_user_access: false
```

Note: depending on the inclusion order of your config.yml you may need to clear the config setting 
before updating it. In this case use the following in your `mysite/_config.php`:

```php
SilverStripe\Core\Config\Config::modify()->remove(SilverStripe\Blog\Model\Blog::class, 'grant_user_access');
```

If you create your own permissions and want to ensure the pool of possible selectable users includes 
those with this permission you can set the checked permission in `config.yml` with:

```yaml
SilverStripe\Blog\Model\Blog:
  grant_user_permission: SOME_PERMISSION_HERE
```

## Restricting blog post authors selection to a known group
In a blog post when selecting an author it will default to you (the logged in person creating the post), 
however you may be posting on behalf of another person. In this case the selection form field will offer 
all users as potential blog authors. Again for large websites with many thousands of users this can cause 
the site to be slow or non-responsive. We can turn on a filter so that authors need to be in a defined 
user group to be able to be selected as an author.

Enable this in your `config.yml` by adding a group code:

```yaml
SilverStripe\Blog\Model\BlogPost:
  restrict_authors_to_group: 'group_code'
```

## Extension points in Blog and BlogPost users and how to use
Both Blog and BlogPost have methods which return the list of candidate users or authors. If the previously 
mentioned methods of reducing this list are not suitable or you wish to roll your own, you can utilise a 
DataExtension to get the control you require.

For example in BlogPost:

```php
protected function getCandidateAuthors()
{
    if ($restrictedGroup = $this->config()->get('restrict_authors_to_group')) {
        if ($group = Group::get()->filter('Code', $restrictedGroup)->first()) {
            return $group->Members();
        }
    } else {
        $list = Member::get();
        $this->extend('updateCandidateAuthors', $list);
        return $list;
    }
}
```

Note the line `$this->extend('updateCandidateAuthors', $list);` which allows you to call a 
`updateCandidateAuthors` method in a DataExtension to the Blog Post class if you have not set a 
`restrict_authors_to_group` config, further filters the passed 
in Member list before it gets sent back to the form field.

See the documentation on [DataExtension](https://docs.silverstripe.org/en/developer_guides/extending/extensions/) for further implementation notes.

