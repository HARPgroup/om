ifim_wua_change_plot <- function(
  ts1, ts2, wua_table, q_pctile = 0.1, stat_method = 'median',ifim_da_sqmi,
  runid_a = "",
  metric_a = "",
  runid_b = "",
  metric_b = ""
)  {
  q_pctilef <- 100.0 * q_pctile
  #-------------------------------------------------------------------------------------------------
  q1 <- mean(as.numeric(ts1$Flow))
  q2 <- mean(as.numeric(ts2$Flow))
  pctchg <- round( 100.0*(q2 - q1)/q1,1)
  # Assumes that
  # Original:
  # targets <- colnames(WUA.df[-1])
  targets <- colnames(wua_table)[-1]
  wua_ts1 <- wua.at.q_fxn(ts1,wua_table)
  wua_ts1 <- data.frame(ts1,wua_ts1)
  wua_ts1 <- wua_ts1[,-1] #remove Date which can break wua_chg calc
  wua_ts2 <- wua.at.q_fxn(ts2,wua_table)
  wua_ts2 <- data.frame(ts2,wua_ts2)
  wua_ts2 <- wua_ts2[,-1] #remove Date which can break wua_chg calc
  wua_chg <- ((wua_ts1-wua_ts2)/wua_ts1)*100
  # Done previously
  #  wua_chg<-wua_chg[,-1] #remove empty date col
  wua_chg<-wua_chg[,-1] #remove empty flow col
  wua_chg <- (wua_chg)*-1
  Date <- ts1$Date
  Flow <- as.numeric(ts1$Flow)
  Flow2 <- as.numeric(ts2$Flow)
  wua_chg <- data.frame(Date,Flow, Flow2,wua_chg, stringsAsFactors=FALSE)
  #negative means habitat loss

  #-------------------------------------------------------------------------------------------------
  #Add column of month names
  wua_chg [grep('-01-', wua_chg$Date, perl= TRUE), "month" ] <- "Jan"
  wua_chg [grep('-02-', wua_chg$Date, perl= TRUE), "month" ] <- "Feb"
  wua_chg [grep('-03-', wua_chg$Date, perl= TRUE), "month" ] <- "Mar"
  wua_chg [grep('-04-', wua_chg$Date, perl= TRUE), "month" ] <- "Apr"
  wua_chg [grep('-05-', wua_chg$Date, perl= TRUE), "month" ] <- "May"
  wua_chg [grep('-06-', wua_chg$Date, perl= TRUE), "month" ] <- "Jun"
  wua_chg [grep('-07-', wua_chg$Date, perl= TRUE), "month" ] <- "Jul"
  wua_chg [grep('-08-', wua_chg$Date, perl= TRUE), "month" ] <- "Aug"
  wua_chg [grep('-09-', wua_chg$Date, perl= TRUE), "month" ] <- "Sep"
  wua_chg [grep('-10-', wua_chg$Date, perl= TRUE), "month" ] <- "Oct"
  wua_chg [grep('-11-', wua_chg$Date, perl= TRUE), "month" ] <- "Nov"
  wua_chg [grep('-12-', wua_chg$Date, perl= TRUE), "month" ] <- "Dec"
  #-------------------------------------------------------------------------------------------------
  start_date <- min(as.Date(wua_chg$Date))
  end_date <- max(as.Date(wua_chg$Date))

  Jan_data <- wua_chg[which(wua_chg$month == "Jan"),]
  Jan_data_percentile <- as.numeric(quantile(Jan_data$Flow, probs = c(q_pctile)))
  Jan_data <- Jan_data[which(as.numeric(Jan_data$Flow) <= Jan_data_percentile),]
  #Jan_data <- Jan_data[,-2]

  Feb_data <- wua_chg[which(wua_chg$month == "Feb"),]
  Feb_data_percentile <- as.numeric(quantile(Feb_data$Flow, probs = c(q_pctile)))
  Feb_data <- Feb_data[which(Feb_data$Flow <= Feb_data_percentile),]
  #Feb_data <- Feb_data[,-2]

  Mar_data <- wua_chg[which(wua_chg$month == "Mar"),]
  Mar_data_percentile <- as.numeric(quantile(Mar_data$Flow, probs = c(q_pctile)))
  Mar_data <- Mar_data[which(Mar_data$Flow <= Mar_data_percentile),]
  #Mar_data <- Mar_data[,-2]

  Apr_data <- wua_chg[which(wua_chg$month == "Apr"),]
  Apr_data_percentile <- as.numeric(quantile(Apr_data$Flow, probs = c(q_pctile)))
  Apr_data <- Apr_data[which(Apr_data$Flow <= Apr_data_percentile),]
  #Apr_data <- Apr_data[,-2]

  May_data <- wua_chg[which(wua_chg$month == "May"),]
  May_data_percentile <- as.numeric(quantile(May_data$Flow, probs = c(q_pctile)))
  May_data <- May_data[which(May_data$Flow <= May_data_percentile),]
  #May_data <- May_data[,-2]

  Jun_data <- wua_chg[which(wua_chg$month == "Jun"),]
  Jun_data_percentile <- as.numeric(quantile(Jun_data$Flow, probs = c(q_pctile)))
  Jun_data <- Jun_data[which(Jun_data$Flow <= Jun_data_percentile),]
  #Jun_data <- Jun_data[,-2]

  Jul_data <- wua_chg[which(wua_chg$month == "Jul"),]
  Jul_data_percentile <- as.numeric(quantile(Jul_data$Flow, probs = c(q_pctile)))
  Jul_data <- Jul_data[which(Jul_data$Flow <= Jul_data_percentile),]
  #Jul_data <- Jul_data[,-2]

  Aug_data <- wua_chg[which(wua_chg$month == "Aug"),]
  Aug_data_percentile <- as.numeric(quantile(Aug_data$Flow, probs = c(q_pctile)))
  Aug_data <- Aug_data[which(Aug_data$Flow <= Aug_data_percentile),]
  #Aug_data <- Aug_data[,-2]

  Sep_data <- wua_chg[which(wua_chg$month == "Sep"),]
  Sep_data_percentile <- as.numeric(quantile(Sep_data$Flow, probs = c(q_pctile)))
  Sep_data <- Sep_data[which(Sep_data$Flow <= Sep_data_percentile),]
  #Sep_data <- Sep_data[,-2]

  Oct_data <- wua_chg[which(wua_chg$month == "Oct"),]
  Oct_data_percentile <- as.numeric(quantile(Oct_data$Flow, probs = c(q_pctile)))
  Oct_data <- Oct_data[which(Oct_data$Flow <= Oct_data_percentile),]
  #oct_data <- Oct_data[,-2]

  Nov_data <- wua_chg[which(wua_chg$month == "Nov"),]
  Nov_data_percentile <- as.numeric(quantile(Nov_data$Flow, probs = c(q_pctile)))
  Nov_data <- Nov_data[which(Nov_data$Flow <= Nov_data_percentile),]
  #Nov_data <- Nov_data[,-2]

  Dec_data <- wua_chg[which(wua_chg$month == "Dec"),]
  Dec_data_percentile <- as.numeric(quantile(Dec_data$Flow, probs = c(q_pctile)))
  Dec_data <- Dec_data[which(Dec_data$Flow <= Dec_data_percentile),]
  #Dec_data <- Dec_data[,-2]

  MAF_data <- wua_chg
  MAF_data_percentile <- as.numeric(quantile(MAF_data$Flow, probs = c(q_pctile)))
  MAF_data <- MAF_data[which(MAF_data$Flow <= MAF_data_percentile),]

  all_pctile_data <- rbind(
    Jan_data, Feb_data, Mar_data, Apr_data, May_data, Jun_data,
    Jul_data, Aug_data, Sep_data, Oct_data, Nov_data, Dec_data
  )
  # what is flow change only when Q is below the input %ile?
  flow_change_pctile <- round(100.0*(mean(all_pctile_data$Flow2) - mean(all_pctile_data$Flow))/mean(all_pctile_data$Flow),1)
  #wua_chg <- wua_chg [,-2]

  dfList <- list(MAF = MAF_data, #wua_chg
                 Jan = Jan_data,
                 Feb = Feb_data,
                 Mar = Mar_data,
                 Apr = Apr_data,
                 May = May_data,
                 Jun = Jun_data,
                 Jul = Jul_data,
                 Aug = Aug_data,
                 Sep = Sep_data,
                 Oct = Oct_data,
                 Nov = Nov_data,
                 Dec = Dec_data)

  #colnames(wua_chg)
  #dfList$Nov

  box_table <- data.frame(metric = c(''),
                          flow = c(''),
                          pctchg = c(''),
                          stringsAsFactors=FALSE)

  #L <- 1
  for (L in 1:length(dfList)) {
    hab.df <- dfList[L]
    hab.df <- data.frame(hab.df)
    #names(hab.df) <- colnames(wua_chg)
    names(hab.df) <- colnames(wua_chg)

    #-------------------------------------------------------------------------------------------------
    #Build dataframe of means of each metric percent change

    col_all <- data.frame(col_all = c(''),
                          stringsAsFactors=FALSE)

    #col_num <- 1
    for (col_num in 2:(length(colnames(hab.df))-1) ) {
      col_data <- hab.df[,col_num]
      col_data <- col_data[!is.na(col_data)] #remove NA values prior to calculating mean
      col_data <- col_data[!is.infinite(col_data)]   #remove Inf values prior to calculating mean

      if (stat_method == "median"){
        col_stat <- median(col_data)
      } else {
        col_stat <- mean(col_data)
      }

      col_name <- colnames(hab.df[col_num])

      col_i <- data.frame(col_stat)
      names(col_i)[1]<-paste(col_name)

      col_all <-data.frame(col_all,col_i)
    }
    col_all <-col_all[,-1] #remove empty col
    col_all <- col_all[,!(names(col_all) %in% "Flow")] # remove Flow column
    col_all <- col_all[,!(names(col_all) %in% "Flow2")] # remove Flow column
    col_all <- col_all[,!(names(col_all) %in% "Date")] # remove Date column


    #col_all <- col_all[,!(names(col_all) %in% "month")] #remove month col

    #col_all <- col_all[,!(names(col_all) %in% "Flow")] # remove Flow column
    #col_all <-col_all[,-1]#remove Flow col
    #col_all <-col_all[,-1] #remove date col
    #col_all = col_all[,!(names(col_all) %in% "month")] #remove month col
    #col_all <- col_all[,!(names(col_all) %in% "Date")] #remove month col
    #-------------------------------------------------------------------------------------------------
    box_table_i <- data.frame(metric = targets,
                              flow = names(dfList[L]),
                              pctchg = as.numeric(col_all[1,]),
                              stringsAsFactors=FALSE)

    box_table <- rbind(box_table, box_table_i)
  } #close df loop

  #-------------------------------------------------------------------------------------------------

  if (length(which(box_table$metric=="boat")) != 0 ){
    print("Removing boat")
    box_table <- box_table[-which(box_table$metric=="boat"),]
  }

  if (length(which(box_table$metric=="canoe")) != 0 ){
    print("Removing canoe")
    box_table <- box_table[-which(box_table$metric=="canoe"),]
  }

  if (length(which(box_table$metric=="canoe_nov")) != 0 ){
    print("Removing canoe_nov")
    box_table <- box_table[-which(box_table$metric=="canoe_nov"),]
  }

  if (length(which(box_table$metric=="canoe_mid")) != 0 ){
    print("Removing canoe_mid")
    box_table <- box_table[-which(box_table$metric=="canoe_mid"),]
  }

  #-------------------------------------------------------------------------------------------------


  box_table <- box_table [-1,] #remove empty first row

  #convert table values to numeric before plotting
  box_table[,3] <- as.numeric(as.character(box_table[,3]))

  plot_title <- paste("Habitat Change: (Run",runid_a," ",metric_a,") vs. (Run",runid_b," ",metric_b,")",sep="")
  sub_title <- paste("Mean Flow Change = ",pctchg,"%", "; Q change =", flow_change_pctile, "% when "," Flows <= ",q_pctilef," %ile","\n","IFIM Site: ",ifim_site_name,"; Drainage Area: ",round(ifim_da_sqmi,1)," sqmi\nTimespan: (",start_date," to ",end_date,")",sep="")

  ifim_plot <- ggplot(box_table, aes(flow,pctchg))+
    geom_boxplot(fill='#A4A4A4', color="darkred")+
 #   geom_text(aes(label=metric),hjust=0, vjust=0)+
    geom_hline(yintercept=0,col='#A4A4A4')+
    labs(title = plot_title,
         subtitle = sub_title)+

    xlab("Month")+
    ylab("Percent Habitat Change")+
    scale_x_discrete(limit = c("MAF",month.abb))
  #scale_y_continuous(limits = c(-10, 100))
  ifim_plot$all_pctile_data <- all_pctile_data
  return(ifim_plot)
}


disabled_stuff <- function () {
  #filename <- paste(ifim_site_name,"10pct",stat_method,"boxplot.png", sep="_")
  #ggsave(file=filename, path = save_directory, width=14, height=8)


  table_export <- data.frame(ifim_site_name,ifim_da_sqmi,box_table)

  output_dir <- paste(save_directory,"\\WUA-CSV",sep="")
  dir.create(output_dir, showWarnings = FALSE) #creates output directory if doesn't exist
  output_dir <- paste(save_directory,"\\WUA-CSV\\",stat_method,sep="")
  dir.create(output_dir, showWarnings = FALSE) #creates output sub-directory if doesn't exist

  write.csv(table_export, file = paste(output_dir,"\\",ifim_site_name,"_",pctchg,"pct_",stat_method,"_tenth_percentile_flow_boxplot.csv",sep=""))


  filename <- paste(ifim_site_name,pctchg,"pct",stat_method,"tenth_percentile_flow_boxplot.png", sep="_")
  ggsave(file=filename, path = output_dir, width=14, height=8)
}
