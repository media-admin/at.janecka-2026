import { Step } from "../step"
export const Frontend = ({
  children,
  ...props
}) => {
    return (
        <Step stepName="shipping" { ...props }>{ children }</Step>
    );
};

export default Frontend;
