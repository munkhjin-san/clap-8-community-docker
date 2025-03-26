export interface ManualProto{
    id?: string
    job_title: string
    job_description: string
    equipment: string
}

export interface Manual {
    id: string;
    title: string;
    rules: Rule[]
    files: ManualFile[]
}
export interface ManualFile {
    contentType: string;
    fileKey: string;
    name: string;
    size: string
}
export interface Rule {
    id: string;
    job: {[key: string]: string}
}