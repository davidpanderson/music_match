# extra fields for user table
#
alter table user
    add

# a performing group
#
create table group (
    id              integer     not null auto_increment,
    create_time     double      not null,
    name            varchar(255)    not null,
    user_id         integer     not null,
    primary key (id)
) engine = InnoDB;

create table group_member (
    create_time     double      not null,
    group_id        integer     not null,
    user_id         integer     not null,
    pending         shortint    not null,
        # waiting for group leader to approve
    unique(group_id, user_id)
);

# a search and its results
#
create table search (
    id              integer     not null auto_increment,
    create_time     double      not null,
    user_id         integer     not null,
    params          text        not null,
        # search params in JSON
    results         text        not null,
        # results (list of user IDs) in JSON
    index(user_id),
    primary key (id)
) engine = InnoDB;
