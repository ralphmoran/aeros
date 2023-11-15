<?php

namespace Classes;

/*
In programming and computer science, a "cluster" generally refers to a group 
of interconnected computers or servers that work together to achieve a common 
goal, whether it's for data storage, processing, or high availability. 
Clusters are often used to improve performance, scalability, and fault 
tolerance in various computing environments. There are different types of 
clusters, including database clusters and server clusters, and I'll explain 
each of them:

1. **Database Cluster**:
   - A database cluster, often referred to as a "DB cluster" or 
   "database cluster," is a group of database servers that work together to 
   provide high availability, load balancing, and fault tolerance for a 
   database system. The most common type of database cluster is a 
   high-availability cluster.
   - High-Availability Cluster: In a high-availability database cluster, 
   multiple database servers are synchronized and run in parallel. If one 
   server fails, another can take over, ensuring continuous availability of 
   the database. Popular database clustering solutions include PostgreSQL 
   with streaming replication and failover, MySQL with Group Replication, 
   and Microsoft SQL Server with AlwaysOn Availability Groups.

2. **Server Cluster**:
   - A server cluster, sometimes called a "server farm" or "server cluster," 
   is a group of interconnected servers that work together to distribute 
   workloads, improve performance, and ensure high availability for services 
   or applications. Server clusters can be used for various purposes, such 
   as web hosting, application hosting, and more.
   - Load Balancing: Server clusters often use load balancers to evenly 
   distribute incoming requests among the servers in the cluster. This helps 
   distribute the workload and prevent any single server from becoming a 
   bottleneck.

3. **Compute Cluster**:
   - A compute cluster is a group of computers or servers used for parallel 
   processing and computation. These clusters are commonly used in scientific 
   and research applications that require significant computational power, 
   such as weather modeling, scientific simulations, and financial modeling.

4. **Storage Cluster**:
   - A storage cluster is a group of storage devices or servers that work 
   together to provide a unified storage solution. These clusters are used 
   to increase storage capacity, improve data redundancy, and ensure data 
   availability. Distributed file systems like Hadoop HDFS and distributed 
   storage systems like Ceph are examples of storage clusters.

5. **Application Cluster**:
   - An application cluster is a group of servers working together to run a 
   specific application, ensuring scalability and high availability. For 
   example, a web application cluster may consist of multiple web servers, 
   application servers, and database servers, all working together to 
   provide a reliable and scalable web service.

Clusters are a fundamental concept in the design of distributed and 
high-performance computing systems. They offer benefits such as scalability, 
fault tolerance, and improved performance, making them a crucial component 
in modern IT infrastructure. The specific technology and architecture used 
for creating clusters can vary based on the intended use case and the 
available resources.
*/

final class Cluster
{
   public static $instance = null;

   public static function __callStatic($method, $arguments)
   {
      dd($method, $arguments, static::$instance);

      if (is_null(static::$instance)) {
         static::$instance = new static();
      }

      return static::$instance;
   }

   public function _connect()
   {
      echo __METHOD__ . PHP_EOL;
      return $this;
   }

   public function get()
   {
      echo __METHOD__ . PHP_EOL;

      return $this;
   }
}
