# Entry Relationship Field

Version: 1.0

### A new way to create master-details (parent-child) relationships with Symphony's sections.

### SPECS ###

- Supports multiple sections for the same relationship.
- Offers developers the possibility to create xslt templates for the field's backend UI.
- Offers a modal UI in order to create/edit for the children.
- Compatible with Symphony associations.
- Supports multiple level (recursive) of associations.
- Aims to be compatible with *all* fields.

#### TODO ####

- Advanced filtering and sorting.


### REQUIREMENTS ###

- Symphony CMS version 2.4.1 and up (as of the day of the last release of this extension)

### INSTALLATION ###

- `git clone` / download and unpack the tarball file
- Put into the extension directory
- Enable/install just like any other extension

See <http://getsymphony.com/learn/tasks/view/install-an-extension/>

##### You can also use the [extension downloader](https://github.com/DeuxHuitHuit/extension_downloader/) to install it. Just search for `entry_relationship_field`. #####

### HOW TO USE ###

- Go to the section editor and add an Entry Relationship field.
- Give it a name.
- Select at least one section that will be permitted as a child.
- Select also the fields you want to be available in the backend and data sources.
- Create backend templates in the workspace/er_templates folder.
	- The name of the filed must be section-handle.xsl
	- Protip: add `?debug` to backend url to see the available xml for each entry.
- (Optional) Select an xsl mode to be able to support multiple templates for the same section.
- (Optional) Select a maximum recursion level for nested fields.
- (Optional) Select a minium and maximum number of elements for this field.


*Voila !*

Come say hi! -> <http://www.deuxhuithuit.com/>

### AKNOWLEDGMENTS ###

This field would not have been created if some other people did not released some really 
cool stuff. We would like to thanks everybody that contributed to those projects:

- [symphonycms/selectbox_link_field](https://github.com/symphonycms/selectbox_link_field)
- [hananils/subsectionmanager](https://github.com/hananils/subsectionmanager)
- [psychoticmeow/content_field](https://github.com/psychoticmeow/content_field)

We basically trashed things that were not necessary and re-implemented things that we likes
from those extensions.

### LICENSE ###

MIT <http://deuxhuithuit.mit-license.org>
