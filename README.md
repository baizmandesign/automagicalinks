# automagicaLinks

This plugin automagically converts text in the body of your pages whose name corresponds to webpages on your website into links. For example, if you have a webpage entitled "Foo Bar" on your website, when the words "Foo Bar" appear in the text of a page's body, they'll be automatically linked to the "Foo Bar" webpage.

To disable a word or phrase from automagically being linked, define and use escape characters. Or add them to the list of global exclusions.

If the automagical mode proves too much, you can use start and end delimiters like so: {{link name}}. If a page with that name exists, it will be linked. If it doesn't, the delimiters will be safely removed.

If the delimiters have been in use on the site but you want to disable autolinking, leave the plugin active to continue to strip out the delimiters.