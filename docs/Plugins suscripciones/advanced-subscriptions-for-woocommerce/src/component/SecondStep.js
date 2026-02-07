import React,{useContext} from 'react';
import { Select, FormGroup, InputLabel, MenuItem, Checkbox, FormControlLabel, FormControl, TextField,TextareaAutosize } from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';
const { __ } = wp.i18n;
import Context from '../store/store';
const useStyles = makeStyles({
      margin: {
        marginBottom: '20px',
      },
});
const SecondStep = (props) => {
    const classes = useStyles();
    const ctx = useContext(Context);
    return ( 
    <>
        <h3 className="aswc-title">{__('Create Subscription Product','advanced-subscriptions-for-woocommerce') }</h3>
        
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductName']}
                onChange={ctx.changeHandler} 
                id="ProductName" 
                name="ProductName" 
                label={__('Product Name','advanced-subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductShortDescription']}
                onChange={ctx.changeHandler} 
                id="ProductShortDescription" 
                name="ProductShortDescription" 
                label={__('Product Short Description','advanced-subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextareaAutosize 
                value={ctx.formFields['ProductDescription']}
                onChange={ctx.changeHandler} 
                id="ProductDescription" 
                name="ProductDescription" 
                aria-label="Product Description"
                placeholder={__('Product Description','advanced-subscriptions-for-woocommerce')}
                minRows={3}
                variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductPrice']}
                onChange={ctx.changeHandler} 
                id="ProductPrice" 
                name="ProductPrice"
                type="number"
                label={__('Subscription Price','advanced-subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['SubscriptionNumber']}
                onChange={ctx.changeHandler} 
                id="SubscriptionNumber"
                name="SubscriptionNumber" 
                type="number"
                label={__('Subscription Interval','advanced-subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" variant="outlined" fullWidth className="fieldsetWrapper">
            <InputLabel id="demo-simple-select-outlined-label">{__('Subscription Frequency','advanced-subscriptions-for-woocommerce') }</InputLabel>
            <Select
                labelId="demo-simple-select-outlined-label"
                name="SubscriptionInterval"
                id="demo-simple-select-outlined"
                value={ctx.formFields['SubscriptionInterval']}
                onChange={ctx.changeHandler}
                label={__('Subscription Frequency','advanced-subscriptions-for-woocommerce')}
                className={classes.margin}>
                <MenuItem value="day">{__('Days', 'advanced-subscriptions-for-woocommerce') }</MenuItem>
                <MenuItem value="week">{ __('Weeks', 'advanced-subscriptions-for-woocommerce') }</MenuItem>
                <MenuItem value="month">{ __( 'Months', 'advanced-subscriptions-for-woocommerce' ) }</MenuItem>
                <MenuItem value="year">{ __( 'Years','advanced-subscriptions-for-woocommerce' ) }</MenuItem>
            </Select>
        </FormControl>
    </>
    )
}
export default SecondStep;