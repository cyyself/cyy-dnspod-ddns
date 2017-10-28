## cyy-dnspod-ddns

你只需要修改里面的config数组，然后把php文件往你的服务器上放，对于windows你可以手动配置计划任务。

对于Linux你可以把以下内容加入`/etc/crontab`中，其中`/home/cyy/ddns.php`替换为你保存ddns.php的目录
```
*/1 * * * * root php /home/cyy/ddns.php
```
