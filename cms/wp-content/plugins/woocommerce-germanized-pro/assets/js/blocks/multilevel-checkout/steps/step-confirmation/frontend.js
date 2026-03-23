import { Step } from "../step"
export const Frontend = ({
  children,
  ...props
}) => {
    return (
        <Step stepName="confirmation" { ...props }>{ children }</Step>
    );
};

export default Frontend;
