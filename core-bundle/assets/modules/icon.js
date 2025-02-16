import manifest from '../../contao/themes/flexible/icons/manifest.json';

export class Icon {
    /**
     * Create an icon template for the given name. You can call "getHtml()"
     * on the result to get the HTML of the included icon(s). The result will
     * include either one image element (light/dark mode share an icon) or
     * two with a color-scheme CSS class, if there are different variants for
     * light/dark mode.
     *
     * @param name The name of the icon (e.g. "edit" or "delete").
     * @param attributes An object of attributes (e.g. {title: 'foo'}).
     * @returns {HTMLTemplateElement}
     */
    static getTemplate(name, attributes = {}) {
        // Make sure the alt attribute gets set
        if (!attributes.hasOwnProperty('alt')) {
            attributes['alt'] = '';
        }

        const source = Icon.getSource(name);

        if (!source) {
            throw Error(`The icon "${name}" does not exist.`);
        }

        const sourceDark = Icon.getSource(name, true);

        const template = document.createElement('template');

        if (sourceDark) {
            template.content.append(Icon._getImage(source, attributes, 'light'));
            template.content.append(Icon._getImage(sourceDark, attributes, 'dark'));
        } else {
            template.content.append(Icon._getImage(source, attributes));
        }

        return template;
    }

    static _getImage(source, attributes, colorScheme = null) {
        const img = document.createElement('img');
        img.src = source;

        for (const [key, value] of Object.entries(attributes)) {
            img.setAttribute(key, value);
        }

        if (colorScheme) {
            img.classList.add(`color-scheme--${colorScheme}`);
        }

        return img;
    }

    static getSource(name, darkScheme = false) {
        const fileName = `${name}${darkScheme ? '--dark' : ''}.svg`;

        return manifest.hasOwnProperty(fileName) ? manifest[fileName] : null;
    }
}
