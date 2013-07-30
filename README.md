ListBuilderField Module
=======================

Two lists of items - a "master" list and a "selected" list.
You can move items between the lists and sort them. In SS this
field is interchangable with CheckboxSetField except for
adding the sort field.

Usage:
------

Same as CheckboxSetField, except that you can specify the column
used for sorting on the many-many relation the field is saved into.
It can also save a comma-delimited list of id's into a text field
instead if needed, in which case the sort column is irrelevant.

```
$fields->push(new ListBuilderField('Slides', 'Slides', Slides()::get()->Map(), 'SortOrder'));
```

TODO:
-----
- Add buttons in the middle to move one/all both ways
- Add selection so you can move more than one

Author:
-------
Mark Guinn <mark@adaircreative.com>

Pull requests welcome! Just stick to the SS coding guidelines.

Licence:
--------
MIT (see LICENSE for text)

