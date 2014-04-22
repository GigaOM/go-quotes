Go-Quotes - Shortcode Quotes
=========================================================

The `go-quotes` plugin allows for shortcode insertion of blockquotes, pullquotes, and inline quotes. Creating semantically correct code with attributions.

Requirements
------------
* jQuery
* [go-config](https://github.com/GigaOM/go-config)

Addresses the following tickets:
--------------------------------
Core Request: https://github.com/GigaOM/gigaom/issues/3950

Hacking
-------
In the config you can change the taxonomy the shortcodes uses for person terms.

Usage
------------
Note: all three shortcodes require closing tags `[quote]Quote text...[/quote]`! All three also add an id to the quote tag for easier linking.

###Pull quotes
* `[pullquote]No, not rich. I am a poor man with money, which is not the same thing.[/pullquote]` - base pull quote, automatically wraps the quote in `<aside>` tags. Wraps the content in `<p>` tags as well.
    This gives you:

```<aside id='quote-1'>
    <p>
        No, not rich. I am a poor man with money, which is not the same thing.
    </p>
</aside>```

* `[pullquote person='Gabriel Garcia Marquez']No, not rich. I am a poor man with money, which is not the same thing.[/pullquote]` - pull quote with the quoted person's name.
    This gives you the same code as above, but will add the person term "gabriel-garcia-marquez" to the post.

* `[pullquote attribution='Gabriel Garcia Marquez']No, not rich. I am a poor man with money, which is not the same thing.[/pullquote]` - pull quote with attribution.
    This will _not_ add the person term to the post. However, it will add the attribution to the bottom of the code like so:

```<aside id='quote-1'>
    <p>
        No, not rich. I am a poor man with money, which is not the same thing.
    </p>
    <footer>
        <cite>
            Gabriel Garcia Marquez
        </cite>
    </footer>
</aside>```

* `[pullquote attribution='Love in the Time of Cholera - Gabriel Garcia Marquez' person='Gabriel Garcia Marquez']No, not rich. I am a poor man with money, which is not the same thing.[/pullquote]` - pull quote with attribution and person term.
    This will add the person term "gabriel-garcia-marquez" to the post. Our code now looks like this:

```<aside id='quote-1'>
    <p>
        No, not rich. I am a poor man with money, which is not the same thing.
    </p>
    <footer>
        <cite>
            <a href='http://search.gigaom.com/person/gabriel-garcia-marquez/'>
                Love in the Time of Cholera - Gabriel Garcia Marquez
            </a>
        </cite>
    </footer>
</aside>```


###Block quotes
* `[blockquote]Lorem ipsum[/blockquote]` - base block quote, automatically wraps the quote in `<blockquote>` tags. Wraps the content in `<p>` tags as well.

###Inline quotes
* `[quote]Lorem ipsum[/quote]` - base inline quote, automatically wraps the quote in `<q>` tags which means the browser will wrap them in quotes.

Known Issues
------------
* If you manually delete a term and update, the term will not be reapplied unless you change the quote content (caching?)
* This plugin does not apply styles on its own! It is up to the theme and/or dev to create applicable styles.