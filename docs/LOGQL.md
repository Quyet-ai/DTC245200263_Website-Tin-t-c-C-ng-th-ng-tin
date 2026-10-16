# LogQL examples

Open Grafana Explore, choose the Loki datasource, select a recent time range, then run:

```logql
{job="nginx"}
```

```logql
{job="nginx"} |= " 404 "
```

```logql
sum by (service) (rate({job="nginx"} [5m]))
```

Generate a few requests first by opening the site and a missing URL, for example `/not-found-demo`.
