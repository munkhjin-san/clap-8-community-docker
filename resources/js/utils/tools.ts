import Autolinker from 'autolinker';
import { DateTime } from 'luxon'
import { customRef } from 'vue'
import { filesize } from 'filesize';
import { useTheme } from '@/store/theme';
function useDebouncedRef(value:any, delay = 200) {
    let timeout:ReturnType<typeof setTimeout> | number = 300;
    return customRef((track, trigger) => {
      return {
        get() {
          track()
          return value
        },
        set(newValue) {
          clearTimeout(timeout)
          timeout = setTimeout(() => {
            value = newValue
            trigger()
          }, delay)
        }
      }
    })
  }
const parseNumber = (v) => {
    const n = parseInt(String(v ?? "").replace(/,/g, ""), 10);
    return Number.isNaN(n) ? 0 : n;
};
const yenFmt = (n) => (n ? `¥${Number(n).toLocaleString("ja-JP")}` : "—");
const EXPENSE_ITEMS = [
  { key: "salary", label: "給料手当" },
  { key: "outsourcing", label: "外注費" },
  { key: "travel", label: "旅費交通費" },
  { key: "communication", label: "通信費" },
  { key: "supplies", label: "消耗品費" },
  { key: "vehicle", label: "車両費" },
  { key: "rent", label: "地代家賃" },
  { key: "rental", label: "賃借料" },
  { key: "lease", label: "リース料" },
  { key: "other", label: "その他" },
];
const oikawaMap = [
  { id: 1,  name: 'guts',     size: '40px' },
  { id: 2,  name: 'banzai',   size: '40px' },
  { id: 3,  name: 'like',     size: '40px' },
  { id: 4,  name: 'tea',      size: '40px' },
  { id: 5,  name: 'bow',      size: '40px' },
  { id: 6,  name: 'pray',     size: '40px' },
  { id: 7,  name: 'nervous',  size: '40px' },
  { id: 8,  name: 'angry',    size: '40px' },
  { id: 9,  name: 'cry',      size: '40px' },
  { id: 10, name: 'love',     size: '40px' },
  { id: 11, name: 'rush',     size: '40px' },
  { id: 12, name: 'dead',     size: '40px' },
  { id: 13, name: 'broken',   size: '40px' },
  { id: 14, name: 'huh',      size: '40px' },
  { id: 15, name: 'cat',      size: '38px' },
  { id: 16, name: 'cheer',    size: '40px' },
  { id: 17, name: 'rock',     size: '40px' },
  { id: 18, name: 'mustache', size: '40px' },
  { id: 19, name: 'lazy',     size: '24px' },
  { id: 20, name: 'ramen',    size: '40px' },
  { id: 21, name: 'reaper',   size: '42px' },
  { id: 22, name: 'angel',    size: '45px' },
  { id: 23, name: 'mummy',    size: '42px' },
  { id: 24, name: 'sumo',     size: '40px' },
  { id: 25, name: 'samurai',  size: '45px' },
]
const theme = useTheme()

const mentionFormatter = (text: string | null, withUrl?: boolean, multiple: number = 1) => {
    const cook = text ? text : ''
    const prefix = theme.dark ? 'dark' : 'light'
    const cooked = cook.replace(
        /<a href=\/app\/public\/user\?id=(\d+)>(.*?)<\/a>/g,
        '<span class="mntuser" data-userid="$1">$2</span>'
    ).replace(/\[To:([^\]]+):\]/g, (match, username) => {
        return `<span class="mntuser" data-username="${username}">@${username}</span>`;
    }).replace(/\{#([a-z0-9_-]+(?::[a-z0-9_-]+)?)\}/gi, (match, type) => {
        const matched = oikawaMap.find(ob => ob.name === type)
        if (!matched) return match
        const basePx = parseFloat(matched.size ?? '40px')
        const maxHeight = (basePx * multiple) + 'px'

        const style = `max-height: ${maxHeight};`
        return `<img class="chat-emoji" data-type="${type}" src="/images/reactions/v7/${prefix}_${type}.webp" srcset="/images/reactions/v7/${prefix}_${type}@2x.webp 2x" alt="${type}" style="${style}" />`;
    })
    return withUrl ? urlCheck(cooked) : cooked
}

const urlCheck = (text?: string | null) => {
    if (text) {
        var linkedText = Autolinker.link(text, { stripPrefix: false });
        return linkedText;
    }
}

const timeFormat = (time: number) => {
    const hours = Math.floor(time / 60);
    const minutes = time % 60;                
    return `${hours}時間${minutes}分`;
}

function debounce<T extends (...args: any[]) => any>(func: T, wait: number): (...args: Parameters<T>) => void {
    let timeout: ReturnType<typeof setTimeout>;
    return function(...args: Parameters<T>): void {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const detailedDateOptions = () => {


    const irreguarOptions = [
        { name: '2024年上期（2024.3.1～2024.8.31）', year: '2024', which_half: 'first', short_name: '2024年上期' },
        { name: '2024年下期（2024.9.1～2025.2.28）', year: '2024', which_half: 'second', short_name: '2024年下期' },
        { name: '2025年上期（2025.3.1～2025.9.30）', year: '2025', which_half: 'first', short_name: '2025年上期'},
        { name: '2025年下期（2025.10.1～2026.3.31）', year: '2025', which_half: 'second', short_name: '2025年下期'},
        { name: '2026年上期（2026.4.1～2026.9.30）', year: '2026', which_half: 'first', short_name: '2026年上期'},
        { name: '2026年下期（2026.10.1～2027.3.31）', year: '2026', which_half: 'second', short_name: '2026年下期' },
    ].reverse()
    return irreguarOptions;
}
const evaluationDateOptions = () => {
    let dateArray: string[] = [
        '2024-02-01',
        '2024-08-01',
        '2025-03-01',
        '2025-10-01',
        '2026-04-01',
        '2026-10-01',
    ]
    return dateArray
}
const generalPositions = () => {
    const positions = [
        { name: '一般職', value: 0 },
        { name: 'A', value: 10000 },
        { name: 'B', value: 30000 },
        { name: 'C', value: 50000 },
        { name: 'D', value: 70000 },
        { name: 'E', value: 100000 },
        { name: 'F', value: 150000 },
        { name: 'G', value: 200000 }
    ];
    return positions
}

const taskStatusBackgrounds = ['black', '#eb7a00', 'green']
const DateParser = (date:string) => {
    const instance = customParser(date)
    const today = DateTime.now()
    const format = instance.hasSame(today, 'day') ? 'T' : instance.hasSame(today, 'year') ? 'M / d (ccc) HH:mm' : 'y / M / d (ccc) HH:mm'      
    return instance.toFormat(format)  
}
const decidedAnswers = [
    '完全に理解し、実務で活用できる自信がある',
    '十分に理解できているが、実務での応用に不安がある', 
    'ほとんど理解できていない'
]



const DATE_FORMATS = [
  "yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'", // ISO 8601 with microseconds
  "yyyy-MM-dd HH:mm:ss",             // SQL-like format
];

function customParser(dateStr: string, zone: string = "Asia/Tokyo"): DateTime {
  for (const format of DATE_FORMATS) {
    const dt = DateTime.fromFormat(dateStr, format, { zone });
    if (dt.isValid) return dt;
  }

  // Fallback to ISO parsing if it wasn't matched in formats
  const dtISO = DateTime.fromISO(dateStr, { zone });
  if (dtISO.isValid) return dtISO;

  throw new Error(`Invalid date format: ${dateStr}`);
}

const fileSizeParser = (bytes: number) => {
    if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
    else return filesize(bytes, {standard: "jedec", round: 0});
}
const kintoneFileUrlBuilder = (file:any) => {
    const params = new URLSearchParams();
    params.set('key', file.fileKey);
    params.set('access', 'full');
    params.set('name', file.name);
    return `/kintone_file?${params.toString()}`
}

const amountOfMoneyParser = (amount: number) => {
    if (Number.isNaN(amount)) return '—';
    return new Intl.NumberFormat("ja-JP").format(amount);
}
const truncatedName = (filename: string, maxLength: number) => {
  if (!filename) return ''

  // split extension
  const lastDot = filename.lastIndexOf('.')
  if (lastDot === -1) {
    // no extension, just truncate raw
    return filename.length > maxLength
      ? filename.slice(0, maxLength - 3) + '...'
      : filename
  }

  const name = filename.slice(0, lastDot)
  const ext = filename.slice(lastDot)

  if (filename.length <= maxLength) return filename

  // leave room for extension + ellipsis
  const keep = maxLength - ext.length - 3
  return name.slice(0, keep) + '...' + ext
}
const valueTypeOptions = [
  { name: '件', value: 'amount' },
  { name: '率', value: 'rate' },
  { name: '金', value: 'currency'},
]

const lineOptions = [
  { name: '売上', value: 'sales' },
  { name: '販管費', value: 'expense' },
  { name: '利益', value: 'profit' },
  { name: '利益率', value: 'profit_rate' }
]

const kindOptions = [
  { name: '入力', value: 'input' },
  { name: '計算', value: 'derived' }
]
const TODAY = DateTime.now().toISODate();
const key = (id: number | null) => `weather:${id}:lastDone`;
const markTodayDone = (userId: number | null) => {
  localStorage.setItem(key(userId), TODAY);
} 
const isTodayDone = (userId: number | null) => {
  return localStorage.getItem(key(userId)) === TODAY
}
const contractTypeDefaults = [
  {value: 'outsourcing', label: '業務委託契約', focus: '責任範囲・成果物・損害賠償'},
  {value: 'quasi_mandate', label: '準委任契約', focus: '解除・支払・再委託制限'},
  {value: 'work_contract', label: '請負契約', focus: '検収・保証・瑕疵担保'},
  {value: 'nda', label: '秘密保持契約（NDA）', focus: '守秘義務・期間・違約金'},
  {value: 'maintenance', label: '保守・サポート契約', focus: '継続期間・対応範囲・SLA'},
  {value: 'license', label: '使用許諾契約', focus: '知財範囲・使用制限・責任'},
  {value: 'joint_dev', label: '共同開発契約', focus: '知財共有・成果帰属・競業'},
  {value: 'basic_agreement', label: '基本契約', focus: '契約期間・個別契約との関係'},
  {value: 'subcontract', label: '下請契約', focus: '下請法対応・再委託・価格転嫁'}
]
const contractRoleDefaults = [
  {value: '乙', label: '乙（受託者 / 委託先）'},
  {value: '甲', label: '甲（発注者 / 委託者）'}
]
const isMobile = () => window.matchMedia('(max-width: 768px)').matches

export const PROJECT_STATUS_LABEL: Record<string, string> = {
  draft: '下書き',
  // creating: '作成中',
  pending_director: '承認申請中',
  director_approved: '役員承認済（準備中）',
  running: '進行中',
  // suspended: '一時停止',
  completed: '完了',
  // cancelled: '中止',
  returned: '差し戻し',
  // rejected: '却下',
}
export const linkifyParts = (text: string) => {
  const URL_RE =
    /\b((?:https?:\/\/|www\.)[^\s<>"'`]+)(?<![.,!?;:)\]])/gi;

  const parts: any[] = [];
  let lastIndex = 0;

  text.replace(URL_RE, (match, rawUrl, offset) => {
    // push text before url
    if (offset > lastIndex) {
      parts.push({ type: "text", value: text.slice(lastIndex, offset) });
    }

    const href = rawUrl.startsWith("http") ? rawUrl : `https://${rawUrl}`;
    parts.push({
      type: "link",
      value: match,
      href,
    });

    lastIndex = offset + match.length;
    return match;
  });

  // remaining text
  if (lastIndex < text.length) {
    parts.push({ type: "text", value: text.slice(lastIndex) });
  }

  return parts;
}
const formatSalary = (text?: string) => {
  if (!text) return
  const match = text.match(/([０-９\d]+)等級\s*([０-９\d]+)号俸\s*([０-９\d,]+)/)
  
  if (!match) return text;

  const grade = toFullWidth(match[1].replace(/,/g, ""));
  const step = toFullWidth(match[2].replace(/,/g, ""));
  const amount = Number(match[3].replace(/,/g, "")).toLocaleString("ja-JP");

  return `${grade}等級${step}号俸　${amount}`;
}
const toFullWidth = (val: string) => {
  return val.replace(/\d/g, (d) =>
    String.fromCharCode(d.charCodeAt(0) + 0xFEE0)
  );
}
export { 
    debounce, 
    mentionFormatter, 
    urlCheck, 
    timeFormat, 
    detailedDateOptions, 
    generalPositions,
    evaluationDateOptions,
    taskStatusBackgrounds,
    DateParser,
    decidedAnswers,
    useDebouncedRef,
    customParser,
    fileSizeParser,
    kintoneFileUrlBuilder,
    amountOfMoneyParser,
    truncatedName,
    valueTypeOptions,
    lineOptions,
    kindOptions,
    markTodayDone, 
    isTodayDone,
    contractTypeDefaults,
    contractRoleDefaults,
    isMobile,
    oikawaMap,
    EXPENSE_ITEMS,
    parseNumber,
    yenFmt,
    formatSalary
}