import Autolinker from 'autolinker';


const mentionFormatter = (text: string | null, withUrl?: boolean) => {
    const cook = text ? text : ''
    const cooked = cook.replace(
        /<a href=\/app\/public\/user\?id=(\d+)>(.*?)<\/a>/g,
        '<span class="mntuser" data-userid="$1">$2</span>'
    ).replace(/\[To:([^\]]+):\]/g, (match, username) => {
        return `<span class="mntuser" data-username="${username}">@${username}</span>`;
    })
    return withUrl ? urlCheck(cooked) : cooked
}

const urlCheck = (text?: string | null) => {
    if (text) {
        var linkedText = Autolinker.link(text, { stripPrefix: false });
        return linkedText;
    }
}

export { mentionFormatter, urlCheck }