export type Page = {
    headline: string,
    subHeadline?: string|null,
    type: string,
    headerMedia?: Media|null,
    text?: string|null,
    link?: Link|null,
    ctaButtons: Array<Button>,
    icon?: string|null,
    dataRequiredForSubmit: Object<string>,
    submitType?: string|null,
    submitUrl?: string|null,
    form?: Array<InputRow>|null,
    errorMsg?: string|null
}

type InputRow = {
    id: number,
    inputs?: Array<Input>|null,
    surveyButtons?: Array<SurveyButton>|null,
    link?: Link|null
}

type Input = {
    type: string,
    validation: boolean,
    customErrorMsg: string,
    name: string,
    label: string,
    value?: string | number | null,
    connectedTo: string,
    options: Array<any>
}

type SurveyButton = {
    value: string,
    title: string
}

type Link = {
    link: string,
    title: string
}

type Media = {
    type: string,
    link: string
}

type Button = {
    action: string,
    title: string,
    link: string,
}