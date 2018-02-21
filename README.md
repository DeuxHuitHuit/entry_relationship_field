# Entry Relationship Field [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DeuxHuitHuit/entry_relationship_field/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DeuxHuitHuit/entry_relationship_field/?branch=master)

> A new way to create master-details (parent -> children) relationships with Symphony's sections.

### SPECS

- Supports multiple sections for the same relationship.
- Offers developers the possibility to create xslt templates for the field's backend UI.
- Offers a modal UI in order to create/edit for the children.
- Compatible with Symphony associations.
- Supports multiple level (recursive) of associations.
- Aims to be compatible with *all* fields.

### REQUIREMENTS

- Symphony CMS version 2.7.3 and up (as of the day of the last release of this extension)

### INSTALLATION

- `git clone` / download and unpack the tarball file
- Put into the extension directory
- Enable/install just like any other extension

You can also install it using the [extension downloader](http://symphonyextensions.com/extensions/extension_downloader/).
Just search for `entry_relationship_field`.

For more information, see <http://getsymphony.com/learn/tasks/view/install-an-extension/>

### UPDATE FROM 1.0.x

Some developers may have been relying on some bugs in the xslt templates which may break when updating from versions before 2.0.0.
In fact, under some circumstance, the field would output only their default mode instead of all of them.
If this is the case, your either have to be more precise in your XPath queries or in the field's includable elements.

### HOW TO USE

- Go to the section editor and add an Entry Relationship field.
- Give it a name.
- Select at least one section that will be permitted as children.
- Select also the fields you want to be available in the backend templates and data sources.
    - `x-` prefixed attributes are only available in devkit mode.
- [Create backend templates](#backend-templates) in the `workspace/er-templates` folder.
    - The name of the file must be `included-section-handle.xsl`
    - You need at least one template that matches `entry`
    - Protip: add `?debug` to backend url to see the available xml for each entry.
    - Protip: You can also override the default debug template with     
    `<xsl:template match="/data" mode="debug" priority="1"></xsl:template>`
    - Protip: You can create action buttons yourself, using the [data-attribute api](#Data-attribute-API).
- (Optional) Select a maximum recursion level for nested fields.
- (Optional) Select a minimum and maximum number of elements for this field.
- (Optional) Select an xsl mode to be able to support multiple templates for the same section.
- (Optional) Select an xsl mode to customize the publish table view.
- (Optional) Select an xsl mode to customize the publish action bar.
- (Optional) Create some Reverse Relationship fields to be able to manage the relation in both sections!

There is also a [screen cast available](https://www.screenr.com/pDDN)

### Backend templates

#### Entries / publish view templates

Here's what a basic backend template should look like.

```xslt
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="entry">
    <div>
        <h1>
            <xsl:value-of select="./*[1]" />
        </h1>
    </div>
</xsl:template>

</xsl:stylesheet>
```

#### Field action bar template

Beware: this template must be in a xsl file named like the current section's handle (not the targeted sections)

```xslt
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="field" mode="[field settings mode]">
    <div>
        <xsl:if test="allow-new = 'yes'">
            <button type="button" class="create" data-create="[section-id]">Custom create new</button>
        </xsl:if>
    </div>
</xsl:template>

</xsl:stylesheet>
```

#### Default templates

Since version 2.0.0, the extension ships with default xsl templates that can be imported in your customized templates.
Also, feel free to copy and change them as required for your current project.

```xslt
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="../../extensions/entry_relationship_field/er-templates/action-bar.xsl" />
<xsl:import href="../../extensions/entry_relationship_field/er-templates/entry.xsl" />

<xsl:template match="entry">
    <xsl:apply-templates select="." mode="content" />
</xsl:template>

<xsl:template match="entry" mode="[field settings mode for publish table view]">
    <xsl:apply-templates select="." mode="table-view" />
</xsl:template>

<xsl:template match="field" mode="[field settings mode for the action bar]">
    <xsl:apply-templates select="." mode="action-bar" />
</xsl:template>

</xsl:stylesheet>
```

### Data-attribute API

In your backend template, you can create button that uses the same features as the default ones.
The only markup needed is a data-attribute on the button.    
The provided actions are:

- Edit entry `data-edit="{entry-id}"`
- Unlink entry `data-unlink="{entry-id}"`
- Link entry `data-link="{section-handle}"`
- Search entry `data-search="{section-handle}"`
- Delete entry `data-delete="{entry-id}"`
- Create entry `data-create="{section-handle}"`
- Replace entry `data-replace="{entry-id}"`
- Orderable handle selector `data-orderable-handle=""`
- Collapsible selectors
    - Handle `data-collapsible-handle=""`
    - Content `data-collapsible-content=""`
- Insert a specified index `data-insert=""` (only valid with `data-create` and `data-link`)
    - Leaving the `data-insert` attribute empty will make the insertion after the current entry.
    - Setting a number will insert after that index.
    - Setting -1 will insert before the current entry.

Attribute value is always optional: It will revert to the closest data-attribute it can find in the DOM.

If you are trying to act on unrelated sections, add the `data-section="{section-handle}"` attribute alongside the action one.

The search features uses Symphony's suggestion jQuery plug-in. In order for it to work in your template,
use the following html.

```html
<div data-interactive="data-interactive">
    <input data-search="" placeholder="Search for entries" autocomplete="off">
    <ul class="suggestions"></ul>
</div>
```

*No validation is made to check if the feature has been activated in the field's settings.*
The template developer must properly check which setting is enabled in the field's xml.

### AKNOWLEDGMENTS

This field would not have been created if some other people did not released some really 
cool stuff. We would like to thanks everybody that contributed to those projects:

- [symphonycms/selectbox_link_field](https://github.com/symphonycms/selectbox_link_field)
- [hananils/subsectionmanager](https://github.com/hananils/subsectionmanager)
- [psychoticmeow/content_field](https://github.com/psychoticmeow/content_field)

We basically trashed things that were not necessary and re-implemented things that we liked
from those extensions.

### LICENSE

[MIT](https://deuxhuithuit.mit-license.org)

Made with love in Montréal by [Deux Huit Huit](https://deuxhuithuit.com)
