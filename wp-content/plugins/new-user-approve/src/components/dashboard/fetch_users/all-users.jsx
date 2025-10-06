import React, {useEffect, useState} from 'react';
import axios from 'axios';
import { sprintf, __ } from '@wordpress/i18n';
import Pagination from '@mui/material/Pagination';
import Stack from '@mui/material/Stack';
import { 
    Table,
    TableBody,
    TableCell, 
    TableContainer, 
    TableHead, 
    TableRow, 
    Paper, 
    IconButton, 
    Box,
    MenuItem,
    FormControl,
    Select,
Typography } from '@mui/material';
import Skeleton from '@mui/material/Skeleton';
import "react-toastify/dist/ReactToastify.css";
import { toast, ToastContainer } from "react-toastify";
// custom component
import { update_user_status } from '../../../functions';
import { site_url } from '../../../functions';

const All_Users = () => {
    const [usersdata, setUserData] = useState([]);
    const [loading, setLaoding]   = useState(true);
    const [loadingUserId, setLoadingUserId] = useState(null); 
    const [selectedUserId, setSelectedUserId] = useState(null);
    const [error, setError]       = useState(null);
    const [anchorEl, setAnchorEl] = useState(null);
    const [user_id, setUserID]    = useState(null);
    const [columnsOrder, setColumnsOrder] = useState([]);
    const [page, setPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [totalUsers, setTotalUsers] = useState(0);
    const [search, setSearch] = useState("");
    const [searchLoading, setSearchLoading] = useState(true);

    const fetchAllUsers = async () => {
        try{
            setLaoding(true);
            const response = await axios.get(`${NUARestAPI.get_all_users+NUARestAPI.permalink_delimeter}page=${page}&limit=${rowsPerPage}&search=${search}`, 
                { 
                  headers: {
                      'X-WP-Nonce': wpApiSettings.nonce,
                  },
              });
            const data = response.data;
            setUserData(data.users || []);
            setTotalUsers(data.totals || 0);
            if (data.columns_order && Array.isArray(data.columns_order)) {
                setColumnsOrder(data.columns_order);
            }
        }
        catch (error) {
            setError(error);
        }
        finally {
            setLaoding(false);
            setSearchLoading(false);
        }
    }

    useEffect(() =>{
        fetchAllUsers();

    }, [search, page, rowsPerPage]);

    const pageCount = Math.ceil(totalUsers / rowsPerPage);

     // get initial number of page
    const startIndex = (page - 1) * rowsPerPage + 1;
    // get total number of data
    const endIndex = Math.min(page * rowsPerPage, totalUsers);

    const handleRowsPerPageChange = (event) => {
        setRowsPerPage(event.target.value);
        setPage(1); // reset to first page when rows per page changes
    };

    const handleMenuClose = () => {
        setAnchorEl(null);
        setUserID(null);
    }

      const handleMenuAction = async (event, value) => {
        const userId  = value;
        const user_status = event.currentTarget.getAttribute('data-value');
        const userdata = {
          userID : userId, status_label : user_status
        }
        try {
            // setLoadingUserId(userId);
            const response =  await update_user_status('update-user', userdata );
            if(response.message = 'Success') {
               toast.success(
                __('Status has been changed successfully', "new-user-approve"),
                {
                position: "bottom-right",
                autoClose: 2000,
                hideProgressBar: false,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                }
                );
                fetchAllUsers();
                handleMenuClose();
            }
            else if(response.message == 'Failed') {
                setLoadingUserId(false);
                handleMenuClose();
                toast.error(
                __("Failed to update user status", "new-user-approve"),
                {
                    position: "bottom-right",
                    autoClose: 2000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                }
                );
            }
        }
        catch (error) {
              toast.error(
                __("An error occurred", "new-user-approve"),
                {
                  position: "bottom-right",
                  autoClose: 2000,
                  hideProgressBar: false,
                  closeOnClick: true,
                  pauseOnHover: true,
                  draggable: true,
                }
              );
            }
        finally {
            setLoadingUserId(false);
        }

    }

    let iconApprove = (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="0.5" y="0.5" width="23" height="23" rx="1.5" fill="#FAFAFA"/>
        <rect x="0.5" y="0.5" width="23" height="23" rx="1.5" stroke="#E6EBEF"/>
        <path fillRule="evenodd" clipRule="evenodd" d="M11.1 11.1C13.3368 11.1 15.15 9.28675 15.15 7.05C15.15 4.81325 13.3368 3 11.1 3C8.86325 3 7.05 4.81325 7.05 7.05C7.05 9.28675 8.86325 11.1 11.1 11.1ZM11.1 11.1C6.627 11.1 3 14.727 3 19.2H11.6155C11.1724 18.3999 10.92 17.4795 10.92 16.5C10.92 14.3764 12.1062 12.53 13.8521 11.587C12.9942 11.2738 12.0679 11.1 11.1 11.1ZM16.5 21C18.9853 21 21 18.9853 21 16.5C21 14.0147 18.9853 12 16.5 12C14.0147 12 12 14.0147 12 16.5C12 18.9853 14.0147 21 16.5 21ZM19.0418 15.2169L16.3418 17.9169L15.96 18.2987L15.5781 17.9169L13.9581 16.2969L14.7218 15.5332L15.96 16.7713L18.2781 14.4532L19.0418 15.2169Z" fill="#618E5F"/>
        </svg>
        
      );
      
      let iconDeny = (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="0.5" y="0.5" width="23" height="23" rx="1.5" stroke="#E6EBEF"/>
        <path fillRule="evenodd" clipRule="evenodd" d="M11.1 11.1C13.3368 11.1 15.15 9.28675 15.15 7.05C15.15 4.81325 13.3368 3 11.1 3C8.86325 3 7.05 4.81325 7.05 7.05C7.05 9.28675 8.86325 11.1 11.1 11.1ZM11.1 11.1C6.627 11.1 3 14.727 3 19.2H11.6155C11.1724 18.3999 10.92 17.4795 10.92 16.5C10.92 14.3764 12.1062 12.53 13.8521 11.587C12.9942 11.2738 12.0679 11.1 11.1 11.1ZM16.5 21C18.9853 21 21 18.9853 21 16.5C21 14.0147 18.9853 12 16.5 12C14.0147 12 12 14.0147 12 16.5C12 18.9853 14.0147 21 16.5 21Z" fill="#C9605C"/>
        <path className="nua-path-class" d="M19 14.9927L17.4921 16.4994L19 18.0073L18.0073 19L16.4994 17.4921L14.9927 19L14 18.0073L15.5067 16.4994L14 14.9927L14.9927 14L16.4994 15.5067L18.0073 14L19 14.9927Z" fill="beige"/>
      
        </svg>  
      );
    
      let notFound = (
        <svg width="68" height="48" viewBox="0 0 68 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g clipPath="url(#clip0_774_1213)">
        <path d="M30.4245 6.77262H23.8308V0.504771C23.8308 0.225642 23.6037 0 23.3227 0H5.52032C5.23934 0 5.01221 0.225642 5.01221 0.504771V9.85306C5.01221 10.1322 5.23934 10.3578 5.52032 10.3578H28.5166L30.8165 7.59997C31.0908 7.2707 30.8552 6.77262 30.4245 6.77262Z" fill="url(#paint0_linear_774_1213)"/>
        <path d="M36.3433 3.5499L29.4266 11.8435H2.02731C1.15915 11.8435 0.465961 12.5622 0.501293 13.423L1.89776 46.2648C1.93309 47.0772 2.6044 47.7173 3.42209 47.7173H64.0774C64.8934 47.7173 65.5648 47.0788 65.6018 46.2699L67.4979 4.58451C67.5366 3.72206 66.8434 3 65.9736 3H37.516C37.0617 3 36.631 3.20057 36.3416 3.54823L36.3433 3.5499Z" fill="url(#paint1_linear_774_1213)"/>
        </g>
        <defs>
        <linearGradient id="paint0_linear_774_1213" x1="17.9732" y1="0" x2="17.9732" y2="10.3578" gradientUnits="userSpaceOnUse">
        <stop stopColor="#ECF6EE"/>
        <stop offset="1" stopColor="#E1EEE3"/>
        </linearGradient>
        <linearGradient id="paint1_linear_774_1213" x1="33.9997" y1="3" x2="33.9997" y2="47.7173" gradientUnits="userSpaceOnUse">
        <stop stopColor="#ECF6EE"/>
        <stop offset="1" stopColor="#E1EEE3"/>
        </linearGradient>
        <clipPath id="clip0_774_1213">
        <rect width="67" height="48" fill="white" transform="translate(0.5)"/>
        </clipPath>
        </defs>
        </svg>
      );
    let searchIcon = (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 18C11.775 17.9998 13.4989 17.4056 14.897 16.312L19.293 20.708L20.707 19.294L16.311 14.898C17.4051 13.4997 17.9997 11.7755 18 10C18 5.589 14.411 2 10 2C5.589 2 2 5.589 2 10C2 14.411 5.589 18 10 18ZM10 4C13.309 4 16 6.691 16 10C16 13.309 13.309 16 10 16C6.691 16 4 13.309 4 10C4 6.691 6.691 4 10 4Z" fill="#9DADC1"/>
        </svg>
    );

    const defaultColumns = [
        { key: 'user_login', label: __('User', 'new-user-approve') },
        { key: 'user_email', label: __('Email', 'new-user-approve') },
        { key: 'user_registered', label: __('Registration Date', 'new-user-approve') },
        { key: 'nua_status', label: __('Status', 'new-user-approve') },
        { key: 'actions', label: __('Actions', 'new-user-approve') },
    ];
        
    const availableKeys = usersdata.length > 0 ? Object.keys(usersdata[0]) : [];
    
    // Columns order from API
    let apiColumnsOrder = columnsOrder || [];
    
    // user_login will be first always.
    if (!apiColumnsOrder.includes('user_login')) {
        apiColumnsOrder = ['user_login', ...apiColumnsOrder];
    } else {
        apiColumnsOrder = [
        'user_login',
        ...apiColumnsOrder.filter((key) => key !== 'user_login'),
        ];
    }
    
    // Build dynamic columns
    const dynamicColumns = apiColumnsOrder
        .filter((key) => availableKeys.includes(key) || key === 'actions')
        .map((key) => {
        const defaultCol = defaultColumns.find((col) => col.key === key);
        if (defaultCol) return defaultCol;
    
        return {
            key,
            label: key.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase()),
        };
    });
      
    
    return (

        <div className = "all_users_list">
        <div className = "all_users_list_header">
            <h2 className='users_list_title'>  {__('All Users', 'new-user-approve') }</h2>

             
            {/* search field */}
            <div className="nua-header-filters">
                <Box className="nua-search-field-box" component="form" noValidate autoComplete="off"  sx={ {  width: '30ch', position:'relative' } }>
                    <input type="text" className='nua-search-field' placeholder="Search User"  onChange={(e) => { setSearch(e.target.value); setPage(1); setSearchLoading(false); }}/>
                    
                    <div className="code-search-icon">{searchIcon}</div>
                    { searchLoading && (
                    <div className='new-user-approve-loading nua-search-loading'>
                    <div className="nua-spinner"></div></div> )}
                </Box>
                <Stack direction="row" justifyContent="space-between" alignItems="center" mb={2}>
                    <span className='selectSpan' style={{marginRight: 8}}>{__('Show:', 'new-user-approve')}</span>
                    <FormControl size="small" sx={{ minWidth: 120 }}>
                        <Select
                            labelId="rows-per-page-label"
                            value={rowsPerPage}
                            onChange={handleRowsPerPageChange}
                        >
                            <MenuItem value={10}>{__('10', 'new-user-approve')}</MenuItem>
                            <MenuItem value={20}>{__('20', 'new-user-approve')}</MenuItem>
                            <MenuItem value={50}>{__('50', 'new-user-approve')}</MenuItem>
                            <MenuItem value={100}>{__('100', 'new-user-approve')}</MenuItem>
                        </Select>
                    </FormControl>
                    <span className='selectSpan' style={{marginLeft: 8}}>{__('entries', 'new-user-approve')}</span>
                  
                </Stack>
            </div>
           

        </div>
        <TableContainer className="all_users_tbl_container usersTable" component={Paper} sx={{ overflowX: 'auto' }}>
  <Table sx={{ minWidth: 650 }}>
    {/* Dynamic Header */}
    {dynamicColumns.length > 0 && (
      <TableHead>
        <TableRow sx={{ backgroundColor: '#FAFAFA', maxHeight: 50, minHeight: 50, height: 50 }}>
          {dynamicColumns.map((col) => (
            <TableCell key={col.key}>{col.label}</TableCell>
          ))}
        </TableRow>
      </TableHead>
    )}

    <TableBody>
      {loading ? (
        // Skeleton rows
        [...Array(10)].map((_, index) => (
          <TableRow key={index}>
                    {dynamicColumns.length > 0 ? (
                    dynamicColumns.map((col, i) => (
                        <TableCell key={i}>
                        <Skeleton
                            variant={col.key === "actions" ? "circular" : "text"}
                            width={col.key === "actions" ? 24 : "100%"}
                            height={col.key === "actions" ? 24 : 20}
                        />
                        </TableCell>
                    ))
                    ) : (
                    <TableCell colSpan={5}>
                        <Skeleton variant="text" width="100%" height={20} />
                    </TableCell>
                    )}
                </TableRow>
        ))
      ) : usersdata.length > 0 ? (
        usersdata.map((row) => (
          <TableRow key={row.ID}>
            {dynamicColumns.map((col) => {
              if (col.key === "user_login") {
                return (
                  <TableCell key={col.key}>
                    <a
                      href={`${site_url()}/wp-admin/user-edit.php?user_id=${row.ID}`}
                      style={{ textDecoration: "none", color: "#858585" }}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      {row.user_login}
                    </a>
                  </TableCell>
                );
              }

              if (col.key === "nua_status") {
                return (
                  <TableCell key={col.key}>
                    <div className="nua-status-container">
                      <span className={"user-" + row.nua_status}>
                        {row.nua_status.charAt(0).toUpperCase() + row.nua_status.slice(1)}
                      </span>
                      <span>
                        {selectedUserId === row.ID && loading === true ? (
                          <div className="new-user-approve-loading">
                            <div className="nua-spinner"></div>
                          </div>
                        ) : (
                          <span className="loadEmpty" style={{ marginLeft: 13 }}></span>
                        )}
                      </span>
                    </div>
                  </TableCell>
                );
              }

              if (col.key === "actions") {
                return (
                  <TableCell key={col.key} align="center" className="user-action-btn" style={{ display: "flex" }}>
                    <IconButton
                      onClick={row.nua_status !== "approved" ? (event) => handleMenuAction(event, row.ID) : null}
                      data-value="approve"
                      title="Approve"
                      style={{ paddingLeft: "0" }}
                      disabled={row.nua_status === "approved"}
                    >
                      <div className={`status-icon ${row.nua_status === "approved" ? "inactive" : "active"}`}>
                        {iconApprove}
                      </div>
                    </IconButton>

                    <IconButton
                      onClick={row.nua_status !== "denied" ? (event) => handleMenuAction(event, row.ID) : null}
                      data-value="deny"
                      title="Deny"
                      disabled={row.nua_status === "denied"}
                    >
                      <div className={`status-icon ${row.nua_status === "denied" ? "inactive" : "active"}`}>
                        {iconDeny}
                      </div>
                    </IconButton>

                    {loadingUserId === row.ID && (
                      <div className="new-user-approve-loading">
                        <div className="nua-spinner"></div>
                      </div>
                    )}
                  </TableCell>
                );
              }

              // Default for extra/custom fields
              return <TableCell key={col.key}>{row[col.key] || "-"}</TableCell>;
            })}
          </TableRow>
        ))
      ) : (
        <TableRow>
          <TableCell colSpan={dynamicColumns.length || 5}>
            <div className="user-list-empty all-user-empty-list" style={{ textAlign: "center" }}>
              <div className="user-found-error">
                {notFound}
                <span>{__("No Data Available", "new-user-approve")}</span>
                <p className="description">{__("There’s no data available to see!", "new-user-approve")}</p>
              </div>
            </div>
          </TableCell>
        </TableRow>
      )}
    </TableBody>
  </Table>
        </TableContainer>

         {usersdata.length > 0 && (
            <Stack spacing={2} alignItems="center" mt={2} className="nua-table-pagination">
                <Pagination
                count={Math.max(1, pageCount)}
                page={page}
                onChange={(event, value) => setPage(value)}
                variant="outlined"
                shape="rounded"
                className ="nua-nav-pagination"
                />
                <Typography variant="body2" className='nua-table-total-data'>
                    {`${startIndex}–${endIndex} of ${totalUsers}`}<span style={{marginLeft: 5}}>{__('entries', 'new-user-approve')}</span>
                    </Typography>
                </Stack>
        )}
               
         <ToastContainer />
        </div>
    );

}

export default All_Users;