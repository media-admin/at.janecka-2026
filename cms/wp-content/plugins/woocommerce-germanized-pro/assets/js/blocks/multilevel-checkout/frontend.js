import './register-components';

import { MultilevelCheckoutDataProvider, useMultilevelCheckoutDataContext } from "./data";
import './style.scss';
const Frontend = ({
   children,
   checkoutExtensionData,
   extensions,
   cart
}) => {
    return (
        <MultilevelCheckoutDataProvider>
            { children }
        </MultilevelCheckoutDataProvider>
    );
};

export default Frontend;
