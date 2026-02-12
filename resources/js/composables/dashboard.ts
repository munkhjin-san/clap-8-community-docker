import { Evaluation, ProjectGoal, ProjectGoalStep } from "@/interface/projectInterface";
import { computed, ref } from "vue";
import { useApi } from "./api";

const goals = ref<ProjectGoal[]>([])
const evaluationData = ref<Evaluation | null>(null)
const loading = ref(false)
const totalScore = ref(0)
const goalStatusList = [
    '作成中（本人対応中）', 
    '目標を差戻中（本人対応中）', 
    '目標を上席者に申請中（上席者対応中）', 
    '目標を人事に申請中（人事対応中）', 
    '目標の変更申請中（人事対応中）', 
    '目標承認済み（本人対応中）', 
    '結果入力中（本人対応中）',
    '結果を上席者に申請中（上席者対応中）',
    '報告を差戻中（本人対応中）', 
    '結果を上席者承認済み（完了）'
]
const salaryIssueStatusList = [
    '作成中（本人対応中）', 
    '課題を差戻中（本人対応中）', 
    '課題をメンターに申請中（メンター対応中）', 
    '課題を人事に申請中（人事対応中）', 
    '課題の変更申請中（人事対応中）', 
    '課題承認済み（本人対応中）', 
    '結果入力中（本人対応中）',
    '結果をメンターに申請中（メンター対応中）',
    '結果を差戻中（本人対応中）',
    '結果を人事に申請中（人事対応中）', 
    '昇給達成（完了）or 未達成（完了）'
]
const api = useApi()

const issueThemes = [
    {
        level: "社会",
        issues: [
            {
                id: 9,
                level: "社会",
                theme: "創造",
                title: "イノベーション",
                content: "イノベーションは、企業の競争力の向上や生産性の向上、社会の発展、人類の生活や仕事の改善につながります。新しいアイデアや技術を取り入れることで、価値を創造し、経済や社会を発展させることができます。\n イノベーションには、企業や社会をより良いものに変えるためのチャレンジ精神や、一般常識に捕らわれない多角的な発想が必要です。",
                title_full: "社会×創造【イノベーション】"
            },
            {
                id: 8,
                level: "社会",
                theme: "調和",
                title: "ダイバーシティ＆インクルージョン",
                content: "多様なバックグラウンドを持つ人々が互いを尊重し、受け入れることを促進します。\n その結果、異なる視点やアイデアが生まれ、グローバル市場で競争力を向上させることができ、働きやすい環境が確立され、人材の確保と定着の促進にもつながります。",
                title_full: "社会×調和【ダイバーシティ＆インクルージョン】"
            },
            {
                id: 7,
                level: "社会",
                theme: "意義",
                title: "CSR",
                content: "経済的、社会的、環境的影響を考慮し行動することで、企業が社会的信頼性を確保し、環境保護、従業員の福利厚生、コンプライアンス強化、ブランド価値向上などの効果をもたらします。\n また、SDGsへの取り組み、ステークホルダーへの利益還元、企業、地域、社会への貢献意識を持つこともCSRの重要な要素です。",
                title_full: "社会×意義【CSR】"
            }
        ]
    },
    {
        level: "組織",
        issues: [
            {
                id: 6,
                level: "組織",
                theme: "創造",
                title: "リーダーシップ",
                content: "リーダーシップは、組織やグループにおいて、方向性の確立、チームワークの促進、モチベーションの向上、問題解決の支援などの重要な役割を果たします。リーダーシップによって、メンバーが目標に向かって行動し、チームワークを構築し、問題を解決することができます。また、リーダーがメンバーを育成し、成長に貢献することもできます。",
                title_full: "組織×創造【リーダーシップ】"
            },
            {
                id: 5,
                level: "組織",
                theme: "調和",
                title: "ガバナンス",
                content: "適切なルールや仕組みを策定し、遵守することで、信頼性、持続可能性、責任明確化、透明性、成果最大化を実現することを目的としています。\n 具体的な手段としては、法令・社内規定・業務マニュアルの遵守、報連相の実施、率先した職場作り、健全な管理体制、公平な評価などがあります。",
                title_full: "組織×調和【ガバナンス】"
            },
            {
                id: 4,
                level: "組織",
                theme: "意義",
                title: "ミッション・ビジョン・バリュー",
                content: "ミッションは、組織の存在意義や事業目的を、ビジョンは将来の組織の姿を、バリューは重視する価値観を示すものです。\n これらの概念を正確に理解し、自分自身の使命や役割、チームや企業の使命や役割を理解し、十分な貢献をすることが、組織の事業戦略や戦術を正しく把握し、周囲に適切に働きかけるために重要です。",
                title_full: "組織×意義【ミッション・ビジョン・バリュー】"
            }
        ]
    },
    {
        level: "自己",
        issues: [
            {
                id: 3,
                level: "自己",
                theme: "創造",
                title: "キャリア形成",
                content: "自分自身のスキルや価値観、興味などを把握し、将来のキャリアについて明確なビジョンを持ち、そのために具体的な計画を立てることが重要です。\n また、問題解決能力、交渉能力、そしてその計画を実現するための能力が必要です。\n これらの能力を持つことで、よりスムーズにキャリアを形成し、自己成長や成功への道を切り開くことができます。",
                title_full: "自己×創造【キャリア形成】"
            },
            {
                id: 2,
                level: "自己",
                theme: "調和",
                title: "セルフマネジメント",
                content: "自己目標や組織の目標を明確にし、業務の優先順位や期限を把握し、自己管理能力を高めることによって、効率的に業務をこなし、自己成長を促進し、モチベーションを維持する能力のことです。\n 自己啓発やタイムマネジメント、ストレス管理などの能力を身につけることで、仕事において優れた成果を上げることができます。",
                title_full: "自己×調和【セルフマネジメント】"
            },
            {
                id: 1,
                level: "自己",
                theme: "意義",
                title: "自己認識",
                content: "自分自身を客観的に見つめ、自己の価値観、信念、感情、能力、興味関心、人生の目的などを正確に把握し、受容することによって、自己肯定感を持ち、自己価値を認め、尊重することです。\n 自己理解を深めることは、自分自身に対する自信や誇りを高め、自分に対する愛着を育むことができます。\n これによって、自分自身を受け入れることができ、より健全な人間関係を築くことができます。",
                title_full: "自己×意義【自己認識】"
            }
        ]
    }
]

export function useGoal() {
    const getGoals = async (userId: number, year: number, which_half: string) => {
        loading.value = true
        const params = {
            year: year,
            which_half: which_half,
            user_id: userId
        }
        const data = await api.post('/get_outcome_goals', params)
        goals.value = data.project_goals
        evaluationData.value = data.evaluation ?? null
        totalScore.value = data.achievement_total ?? 0
        loading.value = false
    }
    const goalStatus = (status: number) => {
        return status >= 0 && status < goalStatusList.length ? goalStatusList[status] : '不明'
    }
    const salaryIssueStatus = (status: number) => {
        return status >= 0 && status < salaryIssueStatusList.length ? salaryIssueStatusList[status] : '不明'
    }
    const kpiCalculation = (steps: ProjectGoalStep[]) => {
        if(steps && steps.length){
            const totalProgress = steps.reduce((acc: number, step: ProjectGoalStep) => {
                return acc + step.progress
            }, 0)
            
            const maxProgress = steps.length * 100
            return Math.round((totalProgress / maxProgress) * 100)
        }
        return 0
    }
    const overallScore = (goal: ProjectGoal) => {
        if(!goal.steps || goal.steps.length === 0) return goal.achievement_rate
        const kpi = kpiCalculation(goal.steps)
        const kgi = goal.achievement_rate
        const sum = kpi + kgi
        return Math.round(sum / 2)
    }
    const totalOverallScore = computed(() => {
    if (!goals.value.length) return 0

        return goals.value.reduce((acc, goal) => {
            return acc + overallScore(goal)
        }, 0)
    })
    return {
        goals,
        getGoals,
        loading,
        goalStatus,
        salaryIssueStatus,
        goalStatuses: goalStatusList,
        salaryIssueStatuses: salaryIssueStatusList,
        evaluationData,
        totalScore,
        overallScore,
        totalOverallScore,
        issueThemes
    }
}