create table if not exists event (
    eventDateTime date not null,
    eventAction varchar(20) not null,
    callRef int not null,
    eventValue float,
    eventCurrencyCode char(3)
);
