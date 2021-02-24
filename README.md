# YMX-mail

简简单单的PHP smtp发信类

## 更新日志
    1.None

## 安装

### 1.下载ZIP

### 2.Git

```
git clone https://github.com/yimo6/YMX-mail.git
```

### 食用方法

#### 初始化
```
$Ymail = new Ymail(true,true);
```
 -> 参数1: 是否开启SSL(bool,true为开启,false为关闭)
 
 -> 参数2: 是否开启日志(bool,true为开启,false为关闭)

#### 登入
```
$Ymail -> login('xxxx','xxxxxxxx');
```
 -> 参数1: 登录账户
 
 -> 参数2: 登录密码
 
#### 发信
```
$Ymail -> send('Hello','<p>Hello Wolrd</p>','xx@xx.com',true,'System');
```
 -> 参数1: 标题
 
 -> 参数2: 主体内容
 
 -> 参数3: 收信人
 
 -> 参数4: 是否为HTML
 
 -> 参数5: 发信人署名

# 使用许可

[MIT](LICENSE) © Richard Littauer